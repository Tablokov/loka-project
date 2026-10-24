<?php
// Fungsi tambahan hari 3 (Loyalty & Kampanye). Dipakai lewat require_once di halaman terkait.
require_once __DIR__ . '/fungsi.php';

function loy_status_voucher(array $v) {
    if ($v['status_pakai'] === 'terpakai') return ['Terpakai', 'secondary'];
    if ($v['tgl_berakhir'] && $v['tgl_berakhir'] < date('Y-m-d')) return ['Kedaluwarsa', 'danger'];
    if ($v['tgl_mulai'] && $v['tgl_mulai'] > date('Y-m-d')) return ['Belum berlaku', 'warning'];
    return ['Aktif', 'success'];
}
function loy_teks_diskon(array $v) {
    return $v['tipe_diskon'] === 'persen' ? $v['nilai'] . '%' : rupiah($v['nilai']);
}
/** Daftar id pelanggan yang menjadi target suatu jenis kampanye. */
function loy_target(PDO $pdo, $jenis) {
    if ($jenis === 'Pelanggan Baru') {
        $sql = 'SELECT id FROM pelanggan WHERE tgl_daftar >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
    } elseif ($jenis === 'Pelanggan Loyal') {
        $sql = "SELECT p.id FROM pelanggan p
                WHERE EXISTS (SELECT 1 FROM segmentasi_pelanggan s WHERE s.pelanggan_id = p.id AND (s.customer_value = 'High' OR s.kategori_loyalitas = 'Gold'))
                   OR (SELECT COALESCE(SUM(CASE jenis WHEN 'masuk' THEN jumlah ELSE -jumlah END),0) FROM poin_pelanggan pp WHERE pp.pelanggan_id = p.id) >= " . (int)BATAS_GOLD;
    } else { // Engagement: tidak transaksi > 30 hari (atau belum pernah)
        $sql = 'SELECT p.id FROM pelanggan p
                WHERE COALESCE((SELECT MAX(tanggal) FROM transaksi t WHERE t.pelanggan_id = p.id), "1970-01-01") < DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
    }
    return $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
}
function loy_label_target($jenis) {
    return ['Pelanggan Baru' => 'Daftar ≤ 30 hari', 'Pelanggan Loyal' => 'Gold / High Value', 'Engagement' => 'Tidak transaksi > 30 hari'][$jenis] ?? '-';
}
function loy_nomor_wa($hp) {
    $hp = preg_replace('/\D/', '', $hp);
    if (strpos($hp, '0') === 0) $hp = '62' . substr($hp, 1);
    return $hp;
}
