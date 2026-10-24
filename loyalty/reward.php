<?php
$judul = 'Loyalty - Reward';
require __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/fungsi_loyalty.php';
$admin = $_SESSION['user']['role'] === 'admin';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin) {
    cek_csrf();
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'tambah') {
        $nama = trim($_POST['nama'] ?? ''); $poin = (int)($_POST['poin'] ?? 0); $stok = (int)($_POST['stok'] ?? 0);
        if ($nama === '' || $poin <= 0 || $stok < 0) flash('Isi nama, poin (>0), dan stok (>=0) dengan benar.', 'danger');
        else { $pdo->prepare('INSERT INTO reward (nama,poin_dibutuhkan,stok,status) VALUES (?,?,?,?)')->execute([$nama,$poin,$stok,'aktif']); flash('Reward ditambahkan.'); }
    } elseif ($aksi === 'toggle') {
        $pdo->prepare("UPDATE reward SET status = IF(status='aktif','nonaktif','aktif') WHERE id = ?")->execute([(int)$_POST['id']]); flash('Status reward diubah.');
    } elseif ($aksi === 'stok') {
        $pdo->prepare('UPDATE reward SET stok = GREATEST(0, ?) WHERE id = ?')->execute([(int)$_POST['stok'], (int)$_POST['id']]); flash('Stok diperbarui.');
    }
    redirect('loyalty/reward.php');
}
$data = $pdo->query('SELECT * FROM reward ORDER BY status, poin_dibutuhkan')->fetchAll();
include '_tabs.php';
?>
<div class="row g-3">
  <div class="<?= $admin ? 'col-lg-8' : 'col-12' ?>"><div class="kartu p-0"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Reward</th><th class="text-end">Poin</th><th class="text-end">Stok</th><th>Status</th><?php if ($admin): ?><th></th><?php endif; ?></tr></thead><tbody>
    <?php foreach ($data as $r): ?>
      <tr><td><?= e($r['nama']) ?></td><td class="text-end"><?= $r['poin_dibutuhkan'] ?></td><td class="text-end"><?= $r['stok'] ?></td>
        <td><span class="badge text-bg-<?= $r['status'] === 'aktif' ? 'success' : 'secondary' ?>"><?= $r['status'] ?></span></td>
        <?php if ($admin): ?><td class="text-nowrap">
          <form method="post" class="d-inline-flex gap-1"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="aksi" value="stok"><input type="hidden" name="id" value="<?= $r['id'] ?>">
            <input class="form-control form-control-sm" style="width:70px" type="number" min="0" name="stok" value="<?= $r['stok'] ?>" aria-label="Stok <?= e($r['nama']) ?>"><button class="btn btn-sm btn-outline-secondary">Simpan</button></form>
          <form method="post" class="d-inline"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="aksi" value="toggle"><input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button class="btn btn-sm btn-outline-dark"><?= $r['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></td><?php endif; ?></tr>
    <?php endforeach; ?>
    </tbody></table></div></div></div>
  <?php if ($admin): ?><div class="col-lg-4"><form method="post" class="kartu">
    <h2 class="h6">Tambah reward</h2><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="aksi" value="tambah">
    <label class="form-label" for="nama">Nama reward</label><input class="form-control mb-2" id="nama" name="nama" required>
    <label class="form-label" for="poin">Poin dibutuhkan</label><input class="form-control mb-2" id="poin" type="number" min="1" name="poin" required>
    <label class="form-label" for="stok">Stok</label><input class="form-control mb-3" id="stok" type="number" min="0" name="stok" required>
    <button class="btn btn-loka w-100">Tambah</button></form></div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php';
