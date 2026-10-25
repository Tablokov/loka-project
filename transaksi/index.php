<?php
$judul = 'Transaksi';
require __DIR__ . '/../includes/header.php';
$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$q = trim($_GET['q'] ?? '');
$sql = 'SELECT t.*, p.nama, p.kode FROM transaksi t JOIN pelanggan p ON p.id = t.pelanggan_id WHERE t.tanggal BETWEEN ? AND ?';
$par = [$dari, $sampai];
if ($q !== '') { $sql .= ' AND (p.nama LIKE ? OR p.kode LIKE ?)'; $par[] = "%$q%"; $par[] = "%$q%"; }
$sql .= ' ORDER BY t.tanggal DESC, t.id DESC LIMIT 300';
$st = $pdo->prepare($sql); $st->execute($par);
$data = $st->fetchAll();
$jumlah = array_sum(array_column($data, 'total'));
$admin = ($_SESSION['user']['role'] ?? '') === 'admin';
?>
<div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
  <form class="row g-2" method="get">
    <div class="col-auto"><label class="visually-hidden" for="dari">Dari</label><input class="form-control" type="date" id="dari" name="dari" value="<?= e($dari) ?>"></div>
    <div class="col-auto"><label class="visually-hidden" for="sampai">Sampai</label><input class="form-control" type="date" id="sampai" name="sampai" value="<?= e($sampai) ?>"></div>
    <div class="col-auto"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Nama / kode pelanggan" aria-label="Cari pelanggan"></div>
    <div class="col-auto"><button class="btn btn-outline-loka">Tampilkan</button></div>
  </form>
  <a class="btn btn-loka" href="tambah.php"><i class="bi bi-plus-lg"></i> Catat transaksi</a>
</div>
<div class="kartu p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
  <thead><tr><th>Tanggal</th><th>Pelanggan</th><th class="text-end">Total</th><th class="text-end">Poin</th><?php if ($admin): ?><th></th><?php endif; ?></tr></thead>
  <tbody>
  <?php foreach ($data as $t): ?>
    <tr><td><?= tgl($t['tanggal']) ?></td>
      <td><a href="<?= url('pelanggan/detail.php?id=' . $t['pelanggan_id']) ?>"><?= e($t['nama']) ?></a> <span class="text-muted small"><?= e($t['kode']) ?></span></td>
      <td class="text-end"><?= rupiah($t['total']) ?></td><td class="text-end">+<?= $t['poin_didapat'] ?></td>
      <?php if ($admin): ?><td class="text-nowrap text-end"><a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?= $t['id'] ?>" aria-label="Edit transaksi <?= $t['id'] ?>"><i class="bi bi-pencil"></i></a>
        <form method="post" action="hapus.php" class="d-inline" onsubmit="return confirm('Hapus transaksi ini? Poinnya ikut dibatalkan.')"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="id" value="<?= $t['id'] ?>"><button class="btn btn-sm btn-outline-danger" aria-label="Hapus transaksi <?= $t['id'] ?>"><i class="bi bi-trash"></i></button></form></td><?php endif; ?></tr>
  <?php endforeach; ?>
  <?php if (!$data): ?><tr><td colspan="5" class="text-center text-muted py-4">Tidak ada transaksi pada rentang ini.</td></tr><?php endif; ?>
  </tbody>
  <?php if ($data): ?><tfoot><tr class="fw-semibold"><td colspan="2"><?= count($data) ?> transaksi</td><td class="text-end"><?= rupiah($jumlah) ?></td><td></td><?php if ($admin): ?><td></td><?php endif; ?></tr></tfoot><?php endif; ?>
</table></div></div>
<?php require __DIR__ . '/../includes/footer.php';
