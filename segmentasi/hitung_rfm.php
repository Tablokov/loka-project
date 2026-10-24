<?php
require_once __DIR__ . '/../includes/fungsi.php';
wajib_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('segmentasi/index.php');
cek_csrf();

$rows = $pdo->query("
  SELECT p.id,
    DATEDIFF(CURDATE(), (SELECT MAX(tanggal) FROM transaksi t WHERE t.pelanggan_id = p.id)) AS hari,
    (SELECT COUNT(*) FROM transaksi t WHERE t.pelanggan_id = p.id AND t.tanggal >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)) AS freq,
    (SELECT COALESCE(SUM(total),0) FROM transaksi t WHERE t.pelanggan_id = p.id AND t.tanggal >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)) AS mon,
    COALESCE((SELECT SUM(CASE jenis WHEN 'masuk' THEN jumlah ELSE -jumlah END) FROM poin_pelanggan pp WHERE pp.pelanggan_id = p.id),0) AS poin
  FROM pelanggan p")->fetchAll();

$pdo->beginTransaction();
$pdo->exec('DELETE FROM segmentasi_pelanggan');
$ins = $pdo->prepare('INSERT INTO segmentasi_pelanggan (pelanggan_id,skor_r,skor_f,skor_m,segmen,kategori_loyalitas,customer_value,tgl_hitung) VALUES (?,?,?,?,?,?,?,CURDATE())');
foreach ($rows as $r) {
    [$sr, $sf, $sm] = skor_rfm($r['hari'] === null ? null : (int)$r['hari'], (int)$r['freq'], (int)$r['mon']);
    [$nilai, $segmen] = nilai_pelanggan($sr + $sf + $sm);
    $ins->execute([$r['id'], $sr, $sf, $sm, $segmen, kategori_loyalitas($r['poin']), $nilai]);
}
$pdo->commit();
flash('Segmentasi RFM dihitung ulang untuk ' . count($rows) . ' pelanggan.');
redirect('segmentasi/index.php');
