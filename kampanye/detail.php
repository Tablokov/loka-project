<?php
$judul = 'Detail kampanye';
require __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/fungsi_loyalty.php';
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT k.*, v.kode vkode, v.tipe_diskon, v.nilai FROM kampanye k LEFT JOIN voucher v ON v.id = k.voucher_id WHERE k.id = ?');
$st->execute([$id]); $k = $st->fetch();
if (!$k) { flash('Kampanye tidak ditemukan.', 'warning'); redirect('kampanye/index.php'); }
$tg = $pdo->prepare('SELECT t.*, p.nama, p.kode, p.no_hp FROM kampanye_target t JOIN pelanggan p ON p.id = t.pelanggan_id WHERE t.kampanye_id = ? ORDER BY p.nama');
$tg->execute([$id]); $target = $tg->fetchAll();
$kirim = count(array_filter($target, fn($t) => $t['status_kirim'] === 'terkirim'));
$respon = count(array_filter($target, fn($t) => $t['status_respon'] === 'merespon'));
$pesanDasar = trim($k['deskripsi'] ?: $k['nama']) . ($k['vkode'] ? " Gunakan voucher {$k['vkode']} (diskon " . loy_teks_diskon($k) . ') di Kopi LOKA.' : '');
?>
<div class="row g-3 mb-3">
  <div class="col-lg-8"><div class="kartu">
    <div class="d-flex justify-content-between"><h2 class="h5"><?= e($k['nama']) ?></h2>
      <form method="post" class="d-flex gap-2"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="aksi" value="status"><input type="hidden" name="kampanye_id" value="<?= $id ?>">
        <select class="form-select form-select-sm" name="status" aria-label="Status kampanye"><?php foreach (['Draft','Aktif','Selesai'] as $s): ?><option <?= $k['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
        <button class="btn btn-sm btn-outline-loka" formaction="update.php">Ubah</button></form></div>
    <div class="small text-muted mb-2"><?= e($k['jenis']) ?> · target: <?= e($k['target_segmen']) ?> · <?= tgl($k['tgl_mulai']) ?> - <?= tgl($k['tgl_selesai']) ?> · voucher: <?= $k['vkode'] ? e($k['vkode']) : 'tidak ada' ?></div>
    <p class="mb-0"><?= nl2br(e($k['deskripsi'] ?: '-')) ?></p>
  </div></div>
  <div class="col-lg-4"><div class="kartu stat"><div class="text-muted small">Terkirim / Merespon</div>
    <div class="angka"><?= $kirim ?>/<?= count($target) ?> <span class="fs-6 text-muted">· <?= $respon ?> respon</span></div></div></div>
</div>
<div class="kartu p-0"><div class="table-responsive"><table class="table align-middle mb-0">
  <thead><tr><th>Pelanggan</th><th>No. HP</th><th>Kirim</th><th>Respon</th><th></th></tr></thead><tbody>
  <?php foreach ($target as $t): $wa = 'https://wa.me/' . loy_nomor_wa($t['no_hp']) . '?text=' . rawurlencode("Halo {$t['nama']}, $pesanDasar"); ?>
    <tr><td><?= e($t['nama']) ?> <span class="text-muted small"><?= e($t['kode']) ?></span></td><td><?= e($t['no_hp']) ?></td>
      <td><span class="badge text-bg-<?= $t['status_kirim'] === 'terkirim' ? 'success' : 'secondary' ?>"><?= $t['status_kirim'] ?></span></td>
      <td><span class="badge text-bg-<?= ['merespon'=>'success','tidak'=>'danger','belum'=>'secondary'][$t['status_respon']] ?>"><?= $t['status_respon'] ?></span></td>
      <td class="text-nowrap">
        <a class="btn btn-sm btn-success" href="<?= e($wa) ?>" target="_blank" rel="noopener">Kirim WA</a>
        <form method="post" action="update.php" class="d-inline"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="kampanye_id" value="<?= $id ?>"><input type="hidden" name="target_id" value="<?= $t['id'] ?>">
          <button class="btn btn-sm btn-outline-secondary" name="aksi" value="kirim">Tandai terkirim</button>
          <button class="btn btn-sm btn-outline-success" name="aksi" value="respon" onclick="this.form.respon.value='merespon'">Merespon</button>
          <button class="btn btn-sm btn-outline-danger" name="aksi" value="respon" onclick="this.form.respon.value='tidak'">Tidak</button>
          <input type="hidden" name="respon" value=""></form></td></tr>
  <?php endforeach; ?>
  <?php if (!$target): ?><tr><td colspan="5" class="text-center text-muted py-4">Tidak ada pelanggan yang memenuhi kriteria saat kampanye dibuat.</td></tr><?php endif; ?>
</tbody></table></div></div>
<p class="mt-3"><a href="index.php">&larr; Kembali ke daftar kampanye</a></p>
<?php require __DIR__ . '/../includes/footer.php';
