<?php
$judul = 'Catat transaksi';
require __DIR__ . '/../includes/header.php';
$daftar = $pdo->query('SELECT id, kode, nama FROM pelanggan ORDER BY nama')->fetchAll();
$menu = ['Kopi Susu Loka'=>10000,'Kopi Loka Creamy'=>10000,'Salted Caramel'=>12000,'Butterscotch'=>13000,
         'Triple Peach'=>12000,'Cold Apple'=>12000,'Cold Brew'=>10000,'Cold Stone'=>12000,
         'Chocolate Loka'=>12000,'Cotton Candy'=>10000,'Creamy Milk'=>10000,'Matcha'=>10000];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $pid = (int)($_POST['pelanggan_id'] ?? 0);
    $total = (int)($_POST['total'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $cek = $pdo->prepare('SELECT COUNT(*) FROM pelanggan WHERE id = ?'); $cek->execute([$pid]);
    if (!$cek->fetchColumn()) $error = 'Pilih pelanggan terlebih dahulu.';
    elseif ($total <= 0) $error = 'Total transaksi harus lebih dari 0. Pilih menu atau isi total.';
    elseif ($tanggal > date('Y-m-d')) $error = 'Tanggal transaksi tidak boleh di masa depan.';
    else {
        $kat = kategori_loyalitas(total_poin($pdo, $pid));
        $poin = hitung_poin($total, $kat);
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO transaksi (pelanggan_id,tanggal,total,poin_didapat) VALUES (?,?,?,?)')->execute([$pid,$tanggal,$total,$poin]);
            $tid = $pdo->lastInsertId();
            if ($poin > 0) {
                $pdo->prepare("INSERT INTO poin_pelanggan (pelanggan_id,jenis,jumlah,keterangan,tanggal) VALUES (?, 'masuk', ?, ?, ?)")
                    ->execute([$pid, $poin, "Transaksi #$tid" . ($kat !== 'Bronze' ? " (member $kat)" : ''), $tanggal]);
            }
            $pdo->commit();
            flash("Transaksi " . rupiah($total) . " tercatat, pelanggan mendapat $poin poin.");
            redirect('transaksi/index.php');
        } catch (Exception $ex) { $pdo->rollBack(); $error = 'Gagal menyimpan transaksi. Coba lagi.'; }
    }
}
?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="kartu" style="max-width:760px">
  <input type="hidden" name="csrf" value="<?= csrf() ?>">
  <div class="row g-3">
    <div class="col-md-7"><label class="form-label" for="pelanggan_id">Pelanggan</label>
      <select class="form-select" id="pelanggan_id" name="pelanggan_id" required>
        <option value="">Pilih pelanggan</option>
        <?php foreach ($daftar as $d): ?><option value="<?= $d['id'] ?>" <?= (int)($_POST['pelanggan_id'] ?? $_GET['pelanggan_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['kode'] . ' - ' . $d['nama']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="col-md-5"><label class="form-label" for="tanggal">Tanggal</label>
      <input class="form-control" type="date" id="tanggal" name="tanggal" max="<?= date('Y-m-d') ?>" value="<?= e($_POST['tanggal'] ?? date('Y-m-d')) ?>" required></div>
    <div class="col-12">
      <div class="form-label">Menu yang dibeli (jumlah gelas)</div>
      <div class="row g-2">
      <?php foreach ($menu as $nama => $harga): ?>
        <div class="col-6 col-md-4"><div class="input-group input-group-sm">
          <input type="number" min="0" value="0" class="form-control qty" data-harga="<?= $harga ?>" id="m<?= md5($nama) ?>" aria-label="<?= e($nama) ?>" style="max-width:70px">
          <label class="input-group-text flex-grow-1 text-start" for="m<?= md5($nama) ?>" style="white-space:normal"><?= e($nama) ?> <span class="text-muted ms-1"><?= $harga/1000 ?>K</span></label>
        </div></div>
      <?php endforeach; ?>
      </div>
    </div>
    <div class="col-md-6"><label class="form-label" for="total">Total (Rp)</label>
      <input class="form-control" type="number" min="1" step="500" id="total" name="total" required value="<?= e($_POST['total'] ?? '') ?>">
      <div class="form-text">Terisi otomatis dari menu, tetapi boleh diubah (misalnya ada diskon).</div></div>
  </div>
  <div class="mt-4 d-flex gap-2">
    <button class="btn btn-loka">Simpan transaksi</button>
    <a class="btn btn-outline-secondary" href="index.php">Batal</a>
  </div>
</form>
<?php
$skrip_tambahan = <<<JS
<script>
const total = document.getElementById('total');
document.querySelectorAll('.qty').forEach(q => q.addEventListener('input', () => {
  let s = 0; document.querySelectorAll('.qty').forEach(x => s += (parseInt(x.value) || 0) * parseInt(x.dataset.harga));
  total.value = s || '';
}));
</script>
JS;
require __DIR__ . '/../includes/footer.php';
