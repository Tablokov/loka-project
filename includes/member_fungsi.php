<?php
// Fungsi khusus portal pelanggan. Semua berawalan mem_/member_ agar tidak bentrok dengan fungsi lain.
require_once __DIR__ . '/fungsi.php';

const LOKA_WA = '';   // Isi nomor WhatsApp admin format internasional, contoh '6282378890000'. Kosong = tombol WA disembunyikan.
const LOKA_IG = 'https://instagram.com/lokakopi';
const STEMPEL_PENUH = 10;                       // 10 transaksi = 1 gelas gratis
const STEMPEL_HADIAH = 'Gratis 1 Kopi Susu Loka';

function mem_hp($hp) {
    $d = preg_replace('/\D+/', '', (string)$hp);
    if (strpos($d, '62') === 0) $d = '0' . substr($d, 2);
    return $d;
}
function mem_inisial($nama) {
    $c = trim((string)$nama);
    $c = function_exists('mb_substr') ? mb_substr($c, 0, 1) : substr($c, 0, 1);
    return function_exists('mb_strtoupper') ? mb_strtoupper($c) : strtoupper($c);
}
function member_saat_ini(PDO $pdo) {
    if (empty($_SESSION['member_id'])) return null;
    $st = $pdo->prepare('SELECT * FROM pelanggan WHERE id = ?');
    $st->execute([$_SESSION['member_id']]);
    $m = $st->fetch();
    if (!$m) { unset($_SESSION['member_id']); return null; }
    return $m;
}
function wajib_member(PDO $pdo) {
    $m = member_saat_ini($pdo);
    if (!$m) redirect('auth/login.php?tab=pelanggan');
    return $m;
}
/** Info level: kategori sekarang, target level berikutnya, sisa poin, persen progres. */
function mem_level($poin) {
    $kat = kategori_loyalitas($poin);
    if ($kat === 'Gold') return ['kat' => $kat, 'next' => null, 'target' => null, 'sisa' => 0, 'persen' => 100];
    $base = $kat === 'Bronze' ? 0 : BATAS_SILVER;
    $target = $kat === 'Bronze' ? BATAS_SILVER : BATAS_GOLD;
    return ['kat' => $kat, 'next' => $kat === 'Bronze' ? 'Silver' : 'Gold', 'target' => $target,
            'sisa' => max(0, $target - $poin), 'persen' => (int)min(100, max(0, floor(($poin - $base) / ($target - $base) * 100)))];
}
// Pembatasan percobaan login (5 gagal = kunci 5 menit, per sesi browser)
function mem_terkunci() { return !empty($_SESSION['mem_kunci']) && $_SESSION['mem_kunci'] > time(); }
function mem_gagal() {
    $_SESSION['mem_gagal'] = ($_SESSION['mem_gagal'] ?? 0) + 1;
    if ($_SESSION['mem_gagal'] >= 5) { $_SESSION['mem_kunci'] = time() + 300; $_SESSION['mem_gagal'] = 0; }
}
function mem_reset_gagal() { unset($_SESSION['mem_gagal'], $_SESSION['mem_kunci']); }
function mem_login_sebagai($id) {
    session_regenerate_id(true);
    $_SESSION['member_id'] = (int)$id;
    mem_reset_gagal();
}
function mem_label_status($s) {
    return ['Baru' => ['Diterima', 'baru'], 'Diproses' => ['Sedang ditangani', 'proses'], 'Selesai' => ['Selesai', 'selesai']][$s] ?? [$s, 'baru'];
}

/** Versi ketat cek_csrf(): token kosong selalu ditolak (cek_csrf lama menganggap dua string kosong sama). */
function mem_cek_csrf() {
    $t = $_POST['csrf'] ?? '';
    if ($t === '' || !hash_equals($_SESSION['csrf'] ?? '', $t)) { http_response_code(400); die('Sesi formulir tidak valid. Muat ulang halaman.'); }
}

/** Status stempel: n transaksi, siklus selesai, sudah diklaim, tersedia untuk diklaim. */
function stempel_status(PDO $pdo, $pelanggan_id) {
    $n = $pdo->prepare('SELECT COUNT(*) FROM transaksi WHERE pelanggan_id = ?'); $n->execute([$pelanggan_id]); $n = (int)$n->fetchColumn();
    $k = $pdo->prepare('SELECT COUNT(*) FROM klaim_stempel WHERE pelanggan_id = ?'); $k->execute([$pelanggan_id]); $k = (int)$k->fetchColumn();
    $selesai = intdiv($n, STEMPEL_PENUH);
    return ['n' => $n, 'selesai' => $selesai, 'diklaim' => $k, 'tersedia' => max(0, $selesai - $k)];
}
/** Catat aktivitas penting staf untuk jejak audit. */
function catat_log(PDO $pdo, $aksi, $detail = '') {
    $pdo->prepare('INSERT INTO log_aktivitas (user_id, aksi, detail) VALUES (?, ?, ?)')
        ->execute([$_SESSION['user']['id'] ?? null, $aksi, mb_strimwidth_aman($detail, 250)]);
}
function mb_strimwidth_aman($s, $maks) { return strlen($s) > $maks ? substr($s, 0, $maks - 3) . '...' : $s; }

/** Kondisi SQL untuk baris poin 'masuk' milik transaksi tertentu (cocok persis, bukan awalan: #12 tidak cocok dengan #120). */
function sql_poin_transaksi() { return "jenis = 'masuk' AND pelanggan_id = ? AND (keterangan = ? OR keterangan LIKE ?)"; }
function par_poin_transaksi($pid, $tid) { return [$pid, "Transaksi #$tid", "Transaksi #$tid (%"]; }
