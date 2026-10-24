<?php
$judul = 'Komplain';
require __DIR__ . '/../includes/header.php';
$status = $_GET['status'] ?? '';
$sql = 'SELECT k.*, p.nama, p.kode FROM komplain k JOIN pelanggan p ON p.id = k.pelanggan_id';
$par = [];
if (in_array($status, ['Baru','Diproses','Selesai'], true)) { $sql .= ' WHERE k.status = ?'; $par[] = $status; }
$sql .= " ORDER BY (k.status='Selesai'), FIELD(k.prioritas,'Tinggi','Sedang','Rendah'), k.tanggal DESC";
$st = $pdo->prepare($sql); $st->execute($par);
$data = $st->fetchAll();
$wS = ['Baru'=>'danger','Diproses'=>'warning','Selesai'=>'success'];
$wP = ['Tinggi'=>'danger','Sedang'=>'warning','Rendah'=>'secondary'];
?>
<div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
  <div class="btn-group" role="group" aria-label="Filter status">
    <?php foreach (['' => 'Semua', 'Baru' => 'Baru', 'Diproses' => 'Diproses', 'Selesai' => 'Selesai'] as $k => $l): ?>
      <a class="btn btn-sm <?= $status === $k ? 'btn-loka' : 'btn-outline-loka' ?>" href="?status=<?= $k ?>"><?= $l ?></a>
    <?php endforeach; ?>
  </div>
  <a class="btn btn-loka" href="tambah.php"><i class="bi bi-plus-lg"></i> Catat komplain</a>
</div>
<div class="kartu p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
  <thead><tr><th>Tanggal</th><th>Pelanggan</th><th>Kategori</th><th>Keluhan</th><th>Prioritas</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($data as $k): ?>
    <tr><td><?= tgl($k['tanggal']) ?></td><td><?= e($k['nama']) ?></td><td><?= e($k['kategori']) ?></td>
      <td style="max-width:280px"><?= e(mb_strimwidth($k['deskripsi'], 0, 70, '...')) ?></td>
      <td><span class="badge text-bg-<?= $wP[$k['prioritas']] ?>"><?= $k['prioritas'] ?></span></td>
      <td><span class="badge text-bg-<?= $wS[$k['status']] ?>"><?= $k['status'] ?></span></td>
      <td><a class="btn btn-sm btn-outline-loka" href="detail.php?id=<?= $k['id'] ?>">Detail</a></td></tr>
  <?php endforeach; ?>
  <?php if (!$data): ?><tr><td colspan="7" class="text-center text-muted py-4">Tidak ada komplain<?= $status ? " berstatus $status" : '' ?>.</td></tr><?php endif; ?>
  </tbody>
</table></div></div>
<?php require __DIR__ . '/../includes/footer.php';
