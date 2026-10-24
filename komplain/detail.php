<?php
$judul = 'Detail komplain';
require __DIR__ . '/../includes/header.php';
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT k.*, p.nama, p.kode, p.no_hp FROM komplain k JOIN pelanggan p ON p.id = k.pelanggan_id WHERE k.id = ?');
$st->execute([$id]); $k = $st->fetch();
if (!$k) { flash('Komplain tidak ditemukan.', 'warning'); redirect('komplain/index.php'); }
$rw = $pdo->prepare('SELECT r.*, u.nama petugas FROM riwayat_komplain r LEFT JOIN users u ON u.id = r.petugas_id WHERE r.komplain_id = ? ORDER BY r.tanggal DESC, r.id DESC');
$rw->execute([$id]); $riwayat = $rw->fetchAll();
$wS = ['Baru'=>'danger','Diproses'=>'warning','Selesai'=>'success'];
?>
<div class="row g-3">
  <div class="col-lg-5"><div class="kartu">
    <div class="d-flex justify-content-between"><h2 class="h6"><?= e($k['kategori']) ?> · prioritas <?= e($k['prioritas']) ?></h2>
      <span class="badge text-bg-<?= $wS[$k['status']] ?>"><?= $k['status'] ?></span></div>
    <p class="mb-2"><?= nl2br(e($k['deskripsi'])) ?></p>
    <div class="small text-muted">Dicatat <?= tgl($k['tanggal']) ?> dari
      <a href="<?= url('pelanggan/detail.php?id=' . $k['pelanggan_id']) ?>"><?= e($k['nama']) ?></a> (<?= e($k['no_hp']) ?>)</div>
    <hr>
    <h3 class="h6">Tindak lanjut</h3>
    <form method="post" action="update_status.php">
      <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="id" value="<?= $id ?>">
      <label class="form-label" for="status_baru">Status baru</label>
      <select class="form-select mb-2" id="status_baru" name="status_baru">
        <?php foreach (['Baru','Diproses','Selesai'] as $s): ?><option <?= $k['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
      </select>
      <label class="form-label" for="tindakan">Tindakan yang dilakukan</label>
      <textarea class="form-control mb-3" id="tindakan" name="tindakan" rows="3" required placeholder="Contoh: menghubungi pelanggan dan mengganti minuman"></textarea>
      <button class="btn btn-loka">Simpan tindak lanjut</button>
      <a class="btn btn-outline-secondary" href="index.php">Kembali</a>
    </form>
  </div></div>
  <div class="col-lg-7"><div class="kartu">
    <h3 class="h6">Riwayat penyelesaian</h3>
    <ul class="list-unstyled mb-0">
    <?php foreach ($riwayat as $r): ?>
      <li class="py-2 border-bottom">
        <div class="d-flex justify-content-between"><strong><?= e($r['tindakan']) ?></strong>
          <span class="badge text-bg-<?= $wS[$r['status_baru']] ?? 'secondary' ?>"><?= e($r['status_baru']) ?></span></div>
        <div class="small text-muted"><?= date('d/m/Y H:i', strtotime($r['tanggal'])) ?> oleh <?= e($r['petugas'] ?: '-') ?></div>
      </li>
    <?php endforeach; ?>
    </ul>
  </div></div>
</div>
<?php require __DIR__ . '/../includes/footer.php';
