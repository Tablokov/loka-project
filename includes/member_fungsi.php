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