<?php
$judul = 'Catat komplain';
require __DIR__ . '/../includes/header.php';
$daftar = $pdo->query('SELECT id, kode, nama FROM pelanggan ORDER BY nama')->fetchAll();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $pid = (int)($_POST['pelanggan_id'] ?? 0);
    $kat = $_POST['kategori'] ?? '';
    $pri = $_POST['prioritas'] ?? 'Sedang';
    $des = trim($_POST['deskripsi'] ?? '');
    if (!$pid || !in_array($kat, ['Produk','Pelayanan','Kebersihan','Lainnya'], true) || !in_array($pri, ['Rendah','Sedang','Tinggi'], true)) $error = 'Pilih pelanggan, kategori, dan prioritas.';
    elseif ($des === '') $error = 'Isi deskripsi keluhan.';
    else {
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO komplain (pelanggan_id,tanggal,kategori,deskripsi,status,prioritas) VALUES (?,CURDATE(),?,?,'Baru',?)")->execute([$pid,$kat,$des,$pri]);
        $kid = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO riwayat_komplain (komplain_id,tanggal,tindakan,petugas_id,status_baru) VALUES (?,NOW(),'Komplain dicatat',?, 'Baru')")->execute([$kid, $_SESSION['user']['id']]);
        $pdo->commit();
        flash('Komplain dicatat.');
        redirect('komplain/detail.php?id=' . $kid);
    }
}
?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="kartu" style="max-width:760px">
  <input type="hidden" name="csrf" value="<?= csrf() ?>">
  <div class="row g-3">
    <div class="col-12"><label class="form-label" for="pelanggan_id">Pelanggan</label>
      <select class="form-select" id="pelanggan_id" name="pelanggan_id" required>
        <option value="">Pilih pelanggan</option>
        <?php foreach ($daftar as $d): ?><option value="<?= $d['id'] ?>" <?= (int)($_POST['pelanggan_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['kode'] . ' - ' . $d['nama']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-md-6"><label class="form-label" for="kategori">Kategori</label>
      <select class="form-select" id="kategori" name="kategori" required>
        <?php foreach (['Produk','Pelayanan','Kebersihan','Lainnya'] as $k): ?><option <?= ($_POST['kategori'] ?? '') === $k ? 'selected' : '' ?>><?= $k ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-md-6"><label class="form-label" for="prioritas">Prioritas</label>
      <select class="form-select" id="prioritas" name="prioritas">
        <?php foreach (['Rendah','Sedang','Tinggi'] as $k): ?><option <?= ($_POST['prioritas'] ?? 'Sedang') === $k ? 'selected' : '' ?>><?= $k ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-12"><label class="form-label" for="deskripsi">Deskripsi keluhan</label>
      <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" required><?= e($_POST['deskripsi'] ?? '') ?></textarea></div>
  </div>
  <div class="mt-4 d-flex gap-2"><button class="btn btn-loka">Simpan komplain</button><a class="btn btn-outline-secondary" href="index.php">Batal</a></div>
</form>
<?php require __DIR__ . '/../includes/footer.php';
