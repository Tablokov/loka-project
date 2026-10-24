<?php
$judul = 'Segmentasi pelanggan';
require __DIR__ . '/../includes/header.php';
$filter = $_GET['nilai'] ?? '';
$ringkas = $pdo->query('SELECT customer_value v, COUNT(*) n FROM segmentasi_pelanggan GROUP BY customer_value')->fetchAll(PDO::FETCH_KEY_PAIR);
$sql = 'SELECT s.*, p.nama, p.kode FROM segmentasi_pelanggan s JOIN pelanggan p ON p.id = s.pelanggan_id';
$par = [];
if (in_array($filter, ['High','Medium','Low'], true)) { $sql .= ' WHERE s.customer_value = ?'; $par[] = $filter; }
$sql .= ' ORDER BY (s.skor_r+s.skor_f+s.skor_m) DESC, p.nama';
$st = $pdo->prepare($sql); $st->execute($par);
$data = $st->fetchAll();
$terakhir = $pdo->query('SELECT MAX(tgl_hitung) FROM segmentasi_pelanggan')->fetchColumn();
$warna = ['High'=>'success','Medium'=>'warning','Low'=>'secondary'];
?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
  <div class="text-muted small">Terakhir dihitung: <?= $terakhir ? tgl($terakhir) : 'belum pernah' ?>. Pembobotan: R (hari sejak transaksi terakhir), F dan M (3 bulan terakhir), skor 1-3.</div>
  <?php if ($_SESSION['user']['role'] === 'admin'): ?>
  <form method="post" action="hitung_rfm.php"><input type="hidden" name="csrf" value="<?= csrf() ?>">
    <button class="btn btn-loka"><i class="bi bi-arrow-repeat"></i> Hitung ulang RFM</button></form>
  <?php endif; ?>
</div>
<div class="row g-3 mb-3">
  <?php foreach (['High'=>'High Value','Medium'=>'Medium Value','Low'=>'Low Value'] as $k => $lbl): ?>
  <div class="col-4"><a class="text-decoration-none text-reset" href="?nilai=<?= $k ?>"><div class="kartu stat <?= $filter === $k ? 'border-2 border-dark' : '' ?>">
    <div class="text-muted"><?= $lbl ?></div><div class="angka"><?= $ringkas[$k] ?? 0 ?></div></div></a></div>
  <?php endforeach; ?>
</div>
<?php if ($filter): ?><p><a href="index.php">Tampilkan semua segmen</a></p><?php endif; ?>
<div class="kartu p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
  <thead><tr><th>Pelanggan</th><th class="text-center">R</th><th class="text-center">F</th><th class="text-center">M</th><th class="text-center">Total</th><th>Segmen</th><th>Loyalitas</th></tr></thead>
  <tbody>
  <?php foreach ($data as $s): ?>
    <tr><td><a href="<?= url('pelanggan/detail.php?id=' . $s['pelanggan_id']) ?>"><?= e($s['nama']) ?></a> <span class="text-muted small"><?= e($s['kode']) ?></span></td>
      <td class="text-center"><?= $s['skor_r'] ?></td><td class="text-center"><?= $s['skor_f'] ?></td><td class="text-center"><?= $s['skor_m'] ?></td>
      <td class="text-center fw-semibold"><?= $s['skor_r'] + $s['skor_f'] + $s['skor_m'] ?></td>
      <td><span class="badge text-bg-<?= $warna[$s['customer_value']] ?>"><?= e($s['segmen']) ?></span></td>
      <td><span class="badge badge-<?= strtolower($s['kategori_loyalitas']) ?>"><?= e($s['kategori_loyalitas']) ?></span></td></tr>
  <?php endforeach; ?>
  <?php if (!$data): ?><tr><td colspan="7" class="text-center text-muted py-4">Belum ada hasil segmentasi. Klik "Hitung ulang RFM" (login sebagai admin).</td></tr><?php endif; ?>
  </tbody>
</table></div></div>
<?php require __DIR__ . '/../includes/footer.php';
