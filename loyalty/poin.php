<?php
$judul = 'Loyalty - Poin';
require __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/fungsi_loyalty.php';
include '_tabs.php';

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $st = $pdo->prepare('SELECT * FROM pelanggan WHERE id = ?'); $st->execute([$id]); $p = $st->fetch();
    if ($p):
        $saldo = total_poin($pdo, $id);
        $mut = $pdo->prepare('SELECT * FROM poin_pelanggan WHERE pelanggan_id = ? ORDER BY tanggal DESC, id DESC LIMIT 100');
        $mut->execute([$id]); $mut = $mut->fetchAll(); ?>
<div class="kartu mb-3 d-flex justify-content-between align-items-center">
  <div><div class="text-muted small"><?= e($p['kode']) ?></div><h2 class="h5 mb-0"><?= e($p['nama']) ?></h2></div>
  <div class="text-end"><div class="angka h3 mb-0"><?= $saldo ?> poin</div><span class="badge badge-<?= strtolower(kategori_loyalitas($saldo)) ?>"><?= kategori_loyalitas($saldo) ?></span></div>
</div>
<div class="kartu p-0"><div class="table-responsive"><table class="table table-sm mb-0">
  <thead><tr><th>Tanggal</th><th>Keterangan</th><th class="text-end">Poin</th></tr></thead><tbody>
  <?php foreach ($mut as $m): ?><tr><td><?= tgl($m['tanggal']) ?></td><td><?= e($m['keterangan']) ?></td>
    <td class="text-end <?= $m['jenis'] === 'masuk' ? 'text-success' : 'text-danger' ?>"><?= $m['jenis'] === 'masuk' ? '+' : '-' ?><?= $m['jumlah'] ?></td></tr><?php endforeach; ?>
  <?php if (!$mut): ?><tr><td colspan="3" class="text-center text-muted py-3">Belum ada mutasi poin.</td></tr><?php endif; ?>
</tbody></table></div></div>
<p class="mt-3"><a href="poin.php">&larr; Kembali ke daftar poin</a></p>
<?php require __DIR__ . '/../includes/footer.php'; exit; endif; }

$data = $pdo->query("SELECT p.id, p.kode, p.nama,
   COALESCE(SUM(CASE pp.jenis WHEN 'masuk' THEN pp.jumlah ELSE -pp.jumlah END),0) saldo,
   COALESCE(SUM(CASE pp.jenis WHEN 'masuk' THEN pp.jumlah ELSE 0 END),0) masuk
   FROM pelanggan p LEFT JOIN poin_pelanggan pp ON pp.pelanggan_id = p.id GROUP BY p.id ORDER BY saldo DESC, p.nama")->fetchAll();
?>
<p class="text-muted small">Poin otomatis dari transaksi (Rp10.000 = 1 poin). Kategori: Silver ≥ <?= BATAS_SILVER ?> poin (x<?= MULTIPLIER_POIN['Silver'] ?>), Gold ≥ <?= BATAS_GOLD ?> poin (x<?= MULTIPLIER_POIN['Gold'] ?>).</p>
<div class="kartu p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
  <thead><tr><th>Pelanggan</th><th class="text-end">Saldo poin</th><th>Kategori</th><th>Menuju level berikutnya</th><th></th></tr></thead><tbody>
  <?php foreach ($data as $d): $kat = kategori_loyalitas($d['saldo']);
        $target = $kat === 'Bronze' ? BATAS_SILVER : ($kat === 'Silver' ? BATAS_GOLD : null); ?>
    <tr><td><?= e($d['nama']) ?> <span class="text-muted small"><?= e($d['kode']) ?></span></td>
      <td class="text-end fw-semibold"><?= $d['saldo'] ?></td>
      <td><span class="badge badge-<?= strtolower($kat) ?>"><?= $kat ?></span></td>
      <td class="small text-muted"><?= $target ? 'Kurang ' . ($target - $d['saldo']) . ' poin' : 'Level tertinggi' ?></td>
      <td><a class="btn btn-sm btn-outline-loka" href="?id=<?= $d['id'] ?>">Mutasi</a></td></tr>
  <?php endforeach; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/../includes/footer.php';
