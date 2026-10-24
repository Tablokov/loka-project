<?php
$judul = 'Buat kampanye';
require __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/fungsi_loyalty.php';
$jenisList = ['Pelanggan Baru','Pelanggan Loyal','Engagement'];
$voucher = $pdo->query('SELECT id, kode, nama, tgl_berakhir, status_pakai, pelanggan_id, tipe_diskon, nilai FROM voucher WHERE pelanggan_id IS NULL AND status_pakai = "belum" AND (tgl_berakhir IS NULL OR tgl_berakhir >= CURDATE()) ORDER BY id DESC')->fetchAll();
$jumlah = [];
foreach ($jenisList as $j) $jumlah[$j] = count(loy_target($pdo, $j));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $nama = trim($_POST['nama'] ?? ''); $jenis = $_POST['jenis'] ?? '';
    $mulai = $_POST['tgl_mulai'] ?? ''; $akhir = $_POST['tgl_selesai'] ?? '';
    $vid = (int)($_POST['voucher_id'] ?? 0) ?: null; $des = trim($_POST['deskripsi'] ?? '');
    if ($nama === '' || !in_array($jenis, $jenisList, true) || !$mulai || !$akhir) $error = 'Lengkapi nama, jenis, dan periode kampanye.';
    elseif ($akhir < $mulai) $error = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
    else {
        $ids = loy_target($pdo, $jenis);
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO kampanye (nama,jenis,target_segmen,tgl_mulai,tgl_selesai,voucher_id,deskripsi,status) VALUES (?,?,?,?,?,?,?,'Draft')")
            ->execute([$nama, $jenis, loy_label_target($jenis), $mulai, $akhir, $vid, $des]);
        $kid = $pdo->lastInsertId();
        $ins = $pdo->prepare('INSERT INTO kampanye_target (kampanye_id,pelanggan_id) VALUES (?,?)');
        foreach ($ids as $pid) $ins->execute([$kid, $pid]);
        $pdo->commit();
        flash("Kampanye dibuat dengan " . count($ids) . " pelanggan target.");
        redirect('kampanye/detail.php?id=' . $kid);
    }
}
?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="kartu" style="max-width:760px">
  <input type="hidden" name="csrf" value="<?= csrf() ?>">
  <div class="row g-3">
    <div class="col-12"><label class="form-label" for="nama">Nama kampanye</label><input class="form-control" id="nama" name="nama" required value="<?= e($_POST['nama'] ?? '') ?>"></div>
    <div class="col-12"><label class="form-label" for="jenis">Jenis kampanye</label>
      <select class="form-select" id="jenis" name="jenis" required>
        <?php foreach ($jenisList as $j): ?><option value="<?= $j ?>" <?= ($_POST['jenis'] ?? '') === $j ? 'selected' : '' ?>><?= $j ?> - <?= loy_label_target($j) ?> (<?= $jumlah[$j] ?> pelanggan saat ini)</option><?php endforeach; ?>
      </select>
      <div class="form-text">Target diambil otomatis saat kampanye disimpan. Untuk "Pelanggan Loyal", jalankan Hitung ulang RFM lebih dulu agar data High Value terbaru.</div></div>
    <div class="col-md-6"><label class="form-label" for="tgl_mulai">Mulai</label><input class="form-control" type="date" id="tgl_mulai" name="tgl_mulai" value="<?= e($_POST['tgl_mulai'] ?? date('Y-m-d')) ?>" required></div>
    <div class="col-md-6"><label class="form-label" for="tgl_selesai">Selesai</label><input class="form-control" type="date" id="tgl_selesai" name="tgl_selesai" value="<?= e($_POST['tgl_selesai'] ?? date('Y-m-d', strtotime('+14 day'))) ?>" required></div>
    <div class="col-12"><label class="form-label" for="voucher_id">Voucher untuk kampanye (opsional)</label>
      <select class="form-select" id="voucher_id" name="voucher_id"><option value="">Tanpa voucher</option>
        <?php foreach ($voucher as $v): ?><option value="<?= $v['id'] ?>"><?= e($v['kode'] . ' - ' . $v['nama']) ?> (<?= loy_teks_diskon($v) ?>)</option><?php endforeach; ?>
      </select>
      <div class="form-text">Hanya voucher umum yang masih aktif. Buat dulu di menu Loyalty &gt; Voucher.</div></div>
    <div class="col-12"><label class="form-label" for="deskripsi">Isi pesan / deskripsi</label>
      <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" placeholder="Contoh: Kami rindu kamu! Tunjukkan voucher ini di kasir Kopi LOKA."><?= e($_POST['deskripsi'] ?? '') ?></textarea></div>
  </div>
  <div class="mt-4 d-flex gap-2"><button class="btn btn-loka">Simpan kampanye</button><a class="btn btn-outline-secondary" href="index.php">Batal</a></div>
</form>
<?php require __DIR__ . '/../includes/footer.php';
