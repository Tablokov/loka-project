<?php
$judul = 'Detail pelanggan';
require __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/member_fungsi.php';
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM pelanggan WHERE id = ?'); $st->execute([$id]);
$p = $st->fetch();
if (!$p) { flash('Pelanggan tidak ditemukan.', 'warning'); redirect('pelanggan/index.php'); }

$poin = (int)$pdo->query("SELECT COALESCE(SUM(CASE jenis WHEN 'masuk' THEN jumlah ELSE -jumlah END),0) FROM poin_pelanggan WHERE pelanggan_id = $id")->fetchColumn();
$kat = kategori_loyalitas($poin);
$ringkas = $pdo->query("SELECT COUNT(*) n, COALESCE(SUM(total),0) tot, MAX(tanggal) terakhir FROM transaksi WHERE pelanggan_id = $id")->fetch();
$trx = $pdo->query("SELECT * FROM transaksi WHERE pelanggan_id = $id ORDER BY tanggal DESC, id DESC")->fetchAll();
$punyaAkun = (bool)$pdo->query("SELECT COUNT(*) FROM akun_pelanggan WHERE pelanggan_id = $id")->fetchColumn();
$stp = stempel_status($pdo, $id);
?>
<div class="row g-3">
  <div class="col-lg-4"><div class="kartu">
    <div class="d-flex justify-content-between align-items-start">
      <div><div class="text-muted small"><?= e($p['kode']) ?></div><h2 class="h5 mb-0"><?= e($p['nama']) ?></h2></div>
      <span class="badge badge-<?= strtolower($kat) ?>"><?= $kat ?></span>
    </div>
    <dl class="row mt-3 mb-3 small">
      <dt class="col-5">No. HP</dt><dd class="col-7"><?= e($p['no_hp']) ?></dd>
      <dt class="col-5">Email</dt><dd class="col-7"><?= e($p['email'] ?: '-') ?></dd>
      <dt class="col-5">Alamat</dt><dd class="col-7"><?= e($p['alamat'] ?: '-') ?></dd>
      <dt class="col-5">Tgl. lahir</dt><dd class="col-7"><?= tgl($p['tgl_lahir']) ?></dd>
      <dt class="col-5">Terdaftar</dt><dd class="col-7"><?= tgl($p['tgl_daftar']) ?></dd>
      <dt class="col-5">Menu favorit</dt><dd class="col-7"><?= e($p['preferensi'] ?: '-') ?></dd>
    </dl>
    <a class="btn btn-sm btn-outline-loka" href="edit.php?id=<?= $id ?>">Edit data</a>
    <a class="btn btn-sm btn-outline-secondary" href="index.php">Kembali</a>
  </div>
  <div class="kartu mt-3">
    <h3 class="h6 mb-2"><i class="bi bi-person-badge me-1"></i>Akun member</h3>
    <p class="small mb-2"><?= $punyaAkun ? '<span class="badge text-bg-success">Sudah aktif</span> Pelanggan bisa masuk ke portal member.' : '<span class="badge text-bg-secondary">Belum aktif</span> Pelanggan belum punya akun portal.' ?></p>
    <form method="post" action="reset_pin.php" onsubmit="return confirm('<?= $punyaAkun ? 'Reset PIN' : 'Buat akun member' ?> untuk <?= e(addslashes($p['nama'])) ?>? PIN sementara akan ditampilkan sekali.')">
      <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="id" value="<?= $id ?>">
      <button class="btn btn-sm btn-outline-loka"><i class="bi bi-key me-1"></i><?= $punyaAkun ? 'Reset PIN' : 'Buat akun &amp; PIN' ?></button>
    </form>
  </div>
  <div class="kartu mt-3">
    <h3 class="h6 mb-2"><i class="bi bi-cup-hot me-1"></i>Stempel loyalitas</h3>
    <p class="small mb-1"><?= $stp['n'] ?> transaksi &middot; <?= $stp['selesai'] ?> siklus penuh &middot; <?= $stp['diklaim'] ?> sudah diklaim</p>
    <?php if ($stp['tersedia'] > 0): ?>
      <p class="small mb-2"><span class="badge text-bg-warning"><?= $stp['tersedia'] ?> hadiah siap diklaim</span> <?= e(STEMPEL_HADIAH) ?></p>
      <form method="post" action="klaim_stempel.php" onsubmit="return confirm('Catat klaim hadiah stempel untuk <?= e(addslashes($p['nama'])) ?>?')">
        <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="id" value="<?= $id ?>">
        <button class="btn btn-sm btn-loka">Catat klaim stempel</button></form>
    <?php else: ?><p class="small text-muted mb-0">Belum ada hadiah stempel yang siap diklaim.</p><?php endif; ?>
  </div></div>
  <div class="col-lg-8">
    <div class="row g-3 mb-3">
      <div class="col-4"><div class="kartu stat"><div class="text-muted small">Poin</div><div class="angka"><?= $poin ?></div></div></div>
      <div class="col-4"><div class="kartu stat"><div class="text-muted small">Transaksi</div><div class="angka"><?= $ringkas['n'] ?></div></div></div>
      <div class="col-4"><div class="kartu stat"><div class="text-muted small">Total belanja</div><div class="h5 mb-0 mt-2"><?= rupiah($ringkas['tot']) ?></div></div></div>
    </div>
    <div class="kartu p-0"><div class="p-3 pb-0"><h3 class="h6">Riwayat transaksi</h3></div>
      <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>Tanggal</th><th>Total</th><th>Poin</th></tr></thead>
        <tbody>
        <?php foreach ($trx as $t): ?><tr><td><?= tgl($t['tanggal']) ?></td><td><?= rupiah($t['total']) ?></td><td>+<?= $t['poin_didapat'] ?></td></tr><?php endforeach; ?>
        <?php if (!$trx): ?><tr><td colspan="3" class="text-center text-muted py-3">Belum ada transaksi.</td></tr><?php endif; ?>
        </tbody>
      </table></div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php';
