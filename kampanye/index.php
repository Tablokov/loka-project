<?php
$judul = 'Kampanye CRM';
require __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/fungsi_loyalty.php';
$data = $pdo->query('SELECT k.*, v.kode vkode,
   (SELECT COUNT(*) FROM kampanye_target t WHERE t.kampanye_id = k.id) n_target,
   (SELECT COUNT(*) FROM kampanye_target t WHERE t.kampanye_id = k.id AND t.status_respon = "merespon") n_respon
   FROM kampanye k LEFT JOIN voucher v ON v.id = k.voucher_id ORDER BY k.id DESC')->fetchAll();
$w = ['Draft'=>'secondary','Aktif'=>'success','Selesai'=>'dark'];
?>
<div class="d-flex justify-content-end mb-3"><a class="btn btn-loka" href="tambah.php"><i class="bi bi-plus-lg"></i> Buat kampanye</a></div>
<div class="kartu p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
  <thead><tr><th>Kampanye</th><th>Jenis</th><th>Target</th><th>Periode</th><th>Voucher</th><th class="text-end">Respon</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($data as $k): ?>
    <tr><td><?= e($k['nama']) ?></td><td><?= e($k['jenis']) ?><div class="small text-muted"><?= e(loy_label_target($k['jenis'])) ?></div></td>
      <td><?= $k['n_target'] ?> pelanggan</td><td class="small"><?= tgl($k['tgl_mulai']) ?> - <?= tgl($k['tgl_selesai']) ?></td>
      <td><?= $k['vkode'] ? '<code>' . e($k['vkode']) . '</code>' : '-' ?></td>
      <td class="text-end"><?= $k['n_respon'] ?>/<?= $k['n_target'] ?></td>
      <td><span class="badge text-bg-<?= $w[$k['status']] ?>"><?= $k['status'] ?></span></td>
      <td><a class="btn btn-sm btn-outline-loka" href="detail.php?id=<?= $k['id'] ?>">Detail</a></td></tr>
  <?php endforeach; ?>
  <?php if (!$data): ?><tr><td colspan="8" class="text-center text-muted py-4">Belum ada kampanye. Klik "Buat kampanye" untuk mulai.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/../includes/footer.php';
