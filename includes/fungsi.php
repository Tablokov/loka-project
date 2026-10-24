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
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(400); die('Sesi formulir tidak valid. Muat ulang halaman.'); }
}

// ===== Hari 2: poin & RFM =====
const MULTIPLIER_POIN = ['Bronze' => 1.0, 'Silver' => 1.25, 'Gold' => 1.5];
const RFM_R_TINGGI = 7;
const RFM_R_SEDANG = 30;
const RFM_F_TINGGI = 10;
const RFM_F_SEDANG = 5;
const RFM_M_TINGGI = 250000;
const RFM_M_SEDANG = 100000;

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