<?php
// Dipakai tambah.php dan edit.php. Butuh variabel $p (data awal) dan $error.
$menu = ['Kopi Susu Loka','Kopi Loka Creamy','Salted Caramel','Butterscotch','Triple Peach','Cold Apple','Cold Brew','Cold Stone','Chocolate Loka','Cotton Candy','Creamy Milk','Matcha'];
?>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="kartu" style="max-width:760px">
  <input type="hidden" name="csrf" value="<?= csrf() ?>">
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label" for="nama">Nama lengkap</label>
      <input class="form-control" id="nama" name="nama" required value="<?= e($p['nama'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label" for="no_hp">No. HP</label>
      <input class="form-control" id="no_hp" name="no_hp" required inputmode="tel" value="<?= e($p['no_hp'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label" for="email">Email</label>
      <input class="form-control" id="email" type="email" name="email" value="<?= e($p['email'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label" for="tgl_lahir">Tanggal lahir</label>
      <input class="form-control" id="tgl_lahir" type="date" name="tgl_lahir" value="<?= e($p['tgl_lahir'] ?? '') ?>"></div>
    <div class="col-12"><label class="form-label" for="alamat">Alamat</label>
      <input class="form-control" id="alamat" name="alamat" value="<?= e($p['alamat'] ?? '') ?>"></div>
    <div class="col-md-6"><label class="form-label" for="preferensi">Menu favorit</label>
      <select class="form-select" id="preferensi" name="preferensi">
        <option value="">Belum diketahui</option>
        <?php foreach ($menu as $m): ?><option <?= ($p['preferensi'] ?? '') === $m ? 'selected' : '' ?>><?= e($m) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-md-6"><label class="form-label" for="tgl_daftar">Tanggal daftar</label>
      <input class="form-control" id="tgl_daftar" type="date" name="tgl_daftar" required value="<?= e($p['tgl_daftar'] ?? date('Y-m-d')) ?>"></div>
  </div>
  <div class="mt-4 d-flex gap-2">
    <button class="btn btn-loka">Simpan pelanggan</button>
    <a class="btn btn-outline-secondary" href="index.php">Batal</a>
  </div>
</form>
