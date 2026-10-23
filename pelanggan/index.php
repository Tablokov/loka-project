<?php
$judul = 'Pelanggan';
require __DIR__ . '/../includes/header.php';

$q = trim($_GET['q'] ?? '');
$sql = "SELECT p.*,
          COALESCE(SUM(CASE pp.jenis WHEN 'masuk' THEN pp.jumlah ELSE -pp.jumlah END),0) AS poin,
          (SELECT MAX(tanggal) FROM transaksi t WHERE t.pelanggan_id = p.id) AS terakhir
        FROM pelanggan p LEFT JOIN poin_pelanggan pp ON pp.pelanggan_id = p.id";
$par = [];
if ($q !== '') { $sql .= ' WHERE p.nama LIKE ? OR p.no_hp LIKE ? OR p.kode LIKE ?'; $par = ["%$q%","%$q%","%$q%"]; }
$sql .= ' GROUP BY p.id ORDER BY p.nama';
$st = $pdo->prepare($sql); $st->execute($par);
$data = $st->fetchAll();
?>
<div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
  <form class="d-flex gap-2" method="get">
    <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari nama, no. HP, atau kode" aria-label="Cari pelanggan">
    <button class="btn btn-outline-loka">Cari</button>
  </form>
  <a class="btn btn-loka" href="tambah.php"><i class="bi bi-plus-lg"></i> Tambah pelanggan</a>
</div>
<div class="kartu p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
  <thead><tr><th>Kode</th><th>Nama</th><th>No. HP</th><th>Menu favorit</th><th>Poin</th><th>Loyalitas</th><th>Transaksi terakhir</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($data as $p): $kat = kategori_loyalitas($p['poin']); ?>
    <tr>
      <td><?= e($p['kode']) ?></td>
      <td><a href="detail.php?id=<?= $p['id'] ?>"><?= e($p['nama']) ?></a></td>
      <td><?= e($p['no_hp']) ?></td>
      <td><?= e($p['preferensi']) ?></td>
      <td><?= (int)$p['poin'] ?></td>
      <td><span class="badge badge-<?= strtolower($kat) ?>"><?= $kat ?></span></td>
      <td><?= tgl($p['terakhir']) ?></td>
      <td class="text-nowrap">
        <a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?= $p['id'] ?>" aria-label="Edit <?= e($p['nama']) ?>"><i class="bi bi-pencil"></i></a>
        <form method="post" action="hapus.php" class="d-inline" onsubmit="return confirm('Hapus pelanggan <?= e(addslashes($p['nama'])) ?> beserta seluruh transaksinya?')">
          <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="id" value="<?= $p['id'] ?>">
          <button class="btn btn-sm btn-outline-danger" aria-label="Hapus <?= e($p['nama']) ?>"><i class="bi bi-trash"></i></button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$data): ?><tr><td colspan="8" class="text-center text-muted py-4">Belum ada pelanggan<?= $q ? ' dengan kata kunci tersebut' : '' ?>. Klik "Tambah pelanggan" untuk mulai.</td></tr><?php endif; ?>
  </tbody>
</table></div></div>
<div class="small text-muted mt-2"><?= count($data) ?> pelanggan</div>
<?php require __DIR__ . '/../includes/footer.php';
