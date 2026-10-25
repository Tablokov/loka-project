<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';

// Batas kategori loyalitas (poin). Sesuaikan dengan kebutuhan.
// Rp10.000 = 1 poin, jadi batas dibuat kecil agar cocok dengan data simulasi 3 bulan.
const BATAS_SILVER = 15;
const BATAS_GOLD   = 30;

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rupiah($n) { return 'Rp' . number_format((float)$n, 0, ',', '.'); }
function tgl($d) { return $d ? date('d/m/Y', strtotime($d)) : '-'; }
function url($path = '') { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect($path) { header('Location: ' . url($path)); exit; }

function wajib_login() {
    if (empty($_SESSION['user'])) redirect('auth/login.php');
}
function wajib_admin() {
    wajib_login();
    if ($_SESSION['user']['role'] !== 'admin') { http_response_code(403); die('Hanya admin yang boleh membuka halaman ini.'); }
}
function kategori_loyalitas($poin) {
    if ($poin >= BATAS_GOLD) return 'Gold';
    if ($poin >= BATAS_SILVER) return 'Silver';
    return 'Bronze';
}
function flash($pesan = null, $tipe = 'success') {
    if ($pesan !== null) { $_SESSION['flash'] = [$pesan, $tipe]; return; }
    if (!empty($_SESSION['flash'])) { $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f; }
    return null;
}
function csrf() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function cek_csrf() {
    $t = $_POST['csrf'] ?? '';
    // Token kosong selalu ditolak (hash_equals('', '') bernilai true sehingga permintaan tanpa token dulu lolos).
    if ($t === '' || !hash_equals($_SESSION['csrf'] ?? '', $t)) { http_response_code(400); die('Sesi formulir tidak valid. Muat ulang halaman.'); }
}

// ===== Hari 2: poin & RFM =====
// Multiplier poin per kategori loyalitas
const MULTIPLIER_POIN = ['Bronze' => 1.0, 'Silver' => 1.25, 'Gold' => 1.5];
// Batas skor RFM (skor 1-3). Sesuaikan dengan skala usaha.
const RFM_R_TINGGI = 7;    // transaksi terakhir <= 7 hari  -> skor 3
const RFM_R_SEDANG = 30;   // <= 30 hari -> skor 2, selebihnya 1
const RFM_F_TINGGI = 10;   // >= 10 transaksi / 3 bulan -> 3
const RFM_F_SEDANG = 5;    // >= 5 -> 2, selebihnya 1
const RFM_M_TINGGI = 250000; // >= Rp250.000 / 3 bulan -> 3
const RFM_M_SEDANG = 100000; // >= Rp100.000 -> 2, selebihnya 1

function hitung_poin($total, $kategori) {
    return (int)floor(($total / 10000) * (MULTIPLIER_POIN[$kategori] ?? 1));
}
function total_poin(PDO $pdo, $pelanggan_id) {
    $st = $pdo->prepare("SELECT COALESCE(SUM(CASE jenis WHEN 'masuk' THEN jumlah ELSE -jumlah END),0) FROM poin_pelanggan WHERE pelanggan_id = ?");
    $st->execute([$pelanggan_id]);
    return (int)$st->fetchColumn();
}
function skor_rfm($hari, $freq, $monetary) {
    $r = ($hari !== null && $hari <= RFM_R_TINGGI) ? 3 : (($hari !== null && $hari <= RFM_R_SEDANG) ? 2 : 1);
    $f = $freq >= RFM_F_TINGGI ? 3 : ($freq >= RFM_F_SEDANG ? 2 : 1);
    $m = $monetary >= RFM_M_TINGGI ? 3 : ($monetary >= RFM_M_SEDANG ? 2 : 1);
    return [$r, $f, $m];
}
function nilai_pelanggan($skor) {
    if ($skor >= 8) return ['High', 'High Value (Champion)'];
    if ($skor >= 5) return ['Medium', 'Medium Value'];
    return ['Low', 'Low Value (perlu diaktifkan kembali)'];
}

// ===== Hari 3: voucher, kampanye, WhatsApp =====
function wa_link($hp, $pesan = '') {
    $n = preg_replace('/\D+/', '', (string)$hp);
    if (strpos($n, '0') === 0) $n = '62' . substr($n, 1);
    elseif (strpos($n, '62') !== 0) $n = '62' . $n;
    return 'https://wa.me/' . $n . ($pesan !== '' ? '?text=' . rawurlencode($pesan) : '');
}
function status_voucher(array $v) {
    if ($v['status_pakai'] === 'terpakai') return ['Terpakai', 'secondary'];
    $hari = date('Y-m-d');
    if ($v['tgl_berakhir'] && $v['tgl_berakhir'] < $hari) return ['Kedaluwarsa', 'danger'];
    if ($v['tgl_mulai'] && $v['tgl_mulai'] > $hari) return ['Belum mulai', 'warning'];
    return ['Aktif', 'success'];
}
function label_voucher(array $v) {
    return $v['tipe_diskon'] === 'persen' ? $v['nilai'] . '%' : rupiah($v['nilai']);
}
// Target kampanye berdasarkan aturan bisnis
function target_kampanye(PDO $pdo, $jenis) {
    $poin = "(SELECT COALESCE(SUM(CASE jenis WHEN 'masuk' THEN jumlah ELSE -jumlah END),0) FROM poin_pelanggan WHERE pelanggan_id = p.id)";
    switch ($jenis) {
        case 'Pelanggan Baru':
            $where = 'p.tgl_daftar >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)'; break;
        case 'Pelanggan Loyal':
            $where = "($poin >= " . BATAS_GOLD . " OR EXISTS (SELECT 1 FROM segmentasi_pelanggan s WHERE s.pelanggan_id = p.id AND s.customer_value = 'High'))"; break;
        case 'Engagement':
            $where = 'COALESCE((SELECT MAX(tanggal) FROM transaksi t WHERE t.pelanggan_id = p.id), p.tgl_daftar) < DATE_SUB(CURDATE(), INTERVAL 30 DAY)'; break;
        default: return [];
    }
    return $pdo->query("SELECT p.id, p.kode, p.nama, p.no_hp FROM pelanggan p WHERE $where ORDER BY p.nama")->fetchAll();
}
const SEGMEN_KAMPANYE = [
    'Pelanggan Baru'  => 'Daftar ≤ 30 hari',
    'Pelanggan Loyal' => 'Gold / High Value',
    'Engagement'      => 'Tidak transaksi > 30 hari',
];

function potong($s, $n) {
    if (function_exists('mb_strimwidth')) return mb_strimwidth((string)$s, 0, $n, '...');
    return strlen($s) > $n ? substr($s, 0, $n) . '...' : $s;
}
