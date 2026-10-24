<?php
require_once __DIR__ . '/../includes/member_fungsi.php';
$m = wajib_member($pdo);
$judul = 'Riwayat Pesanan'; $aktif = 'riwayat';
require __DIR__ . '/_header.php';

$ring = $pdo->prepare('SELECT COUNT(*) n, COALESCE(SUM(total),0) tot FROM transaksi WHERE pelanggan_id = ?'); $ring->execute([$m['id']]); $ring = $ring->fetch();
$n = (int)$ring['n'];
$siklus = intdiv($n, STEMPEL_PENUH) + 1;                       // siklus ke-berapa
$isi = $n % STEMPEL_PENUH;                                      // stempel terisi di siklus ini
$penuh = $n > 0 && $isi === 0;                                  // siklus baru saja penuh
if ($penuh) { $isi = STEMPEL_PENUH - 1; $siklus--; }
$sisa = STEMPEL_PENUH - ($penuh ? STEMPEL_PENUH : $isi);
$tr = $pdo->prepare('SELECT * FROM transaksi WHERE pelanggan_id = ? ORDER BY tanggal DESC, id DESC LIMIT 100'); $tr->execute([$m['id']]); $trx = $tr->fetchAll();
$mt = $pdo->prepare('SELECT * FROM poin_pelanggan WHERE pelanggan_id = ? ORDER BY tanggal DESC, id DESC LIMIT 100'); $mt->execute([$m['id']]); $mut = $mt->fetchAll();
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
  <div><h1 class="mjudul"><i class="bi bi-receipt text-warning me-2"></i>Riwayat Pesanan</h1><p class="msub">Stempel loyalitas, transaksi, dan mutasi poinmu.</p></div>
  <div class="d-flex gap-2"><div class="mstat"><div class="lbl">Transaksi</div><div class="val"><?= $n ?></div></div><div class="mstat emas"><div class="lbl">Total belanja</div><div class="val fs-4"><?= rupiah($ring['tot']) ?></div></div></div>
</div>

<div class="mk mb-3" style="border-color:rgba(255,196,107,.5)">
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div><span class="mbadge" style="background:#7a1f2b;color:#ffd1d6">SIKLUS <?= $siklus ?> &bull; STEMPEL KUNJUNGAN</span>
      <h2 class="h4 fw-bold mt-2 mb-1">Koleksi Stempel: <?= e(STEMPEL_HADIAH) ?></h2>
      <div class="small text-redup">1 transaksi = 1 stempel &middot; Total transaksi: <strong class="text-warning"><?= $n ?></strong></div></div>
    <div class="text-end"><div class="display-6 fw-bold text-warning lh-1"><?= $penuh ? STEMPEL_PENUH : $isi ?>/<?= STEMPEL_PENUH ?></div>
      <div class="small"><?= $penuh ? 'Stempel penuh! Tunjukkan ke kasir untuk klaim.' : $sisa . ' stempel lagi untuk ' . e(STEMPEL_HADIAH) ?></div></div>
  </div>
  <div class="stempel-grid" role="img" aria-label="<?= $penuh ? STEMPEL_PENUH : $isi ?> dari <?= STEMPEL_PENUH ?> stempel terisi">
    <?php for ($i = 1; $i < STEMPEL_PENUH; $i++): $terisi = $penuh || $i <= $isi; ?>
      <div class="stempel <?= $terisi ? 'isi' : '' ?>"><i class="bi <?= $terisi ? 'bi-cup-hot-fill' : 'bi-cup' ?>"></i>Stempel #<?= $i ?></div>
    <?php endfor; ?>
    <div class="stempel hadiah <?= $penuh ? 'siap' : '' ?>"><i class="bi bi-gift-fill"></i>GRATIS 1 KOPI</div>
  </div>
  <p class="small text-redup mt-3 mb-0">Stempel dihitung otomatis dari transaksi yang dicatat kasir. Klaim hadiah stempel dilakukan langsung di kasir.</p>
</div>

<div class="mk">
  <div class="mtab mb-3" id="tabRiwayat">
    <button type="button" class="aktif" data-t="trx">Transaksi (<?= count($trx) ?>)</button>
    <button type="button" data-t="poin">Mutasi poin (<?= count($mut) ?>)</button>
  </div>
  <div data-panel="trx"><?php if ($trx): ?><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Tanggal</th><th class="text-end">Total</th><th class="text-end">Poin didapat</th></tr></thead><tbody>
    <?php foreach ($trx as $t): ?><tr><td><?= tgl($t['tanggal']) ?></td><td class="text-end"><?= rupiah($t['total']) ?></td><td class="text-end text-warning">+<?= $t['poin_didapat'] ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php else: ?><div class="mkosong"><i class="bi bi-cup-hot"></i>Belum ada transaksi.</div><?php endif; ?></div>
  <div data-panel="poin" hidden><?php if ($mut): ?><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Tanggal</th><th>Keterangan</th><th class="text-end">Poin</th></tr></thead><tbody>
    <?php foreach ($mut as $p): ?><tr><td><?= tgl($p['tanggal']) ?></td><td><?= e($p['keterangan']) ?></td><td class="text-end fw-bold" style="color:<?= $p['jenis'] === 'masuk' ? 'var(--hijau)' : 'var(--merah)' ?>"><?= $p['jenis'] === 'masuk' ? '+' : '-' ?><?= $p['jumlah'] ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php else: ?><div class="mkosong">Belum ada mutasi poin.</div><?php endif; ?></div>
</div>
<?php
$skrip_tambahan = <<<'JS'
<script>
document.querySelectorAll('#tabRiwayat button').forEach(b => b.addEventListener('click', () => {
  document.querySelectorAll('#tabRiwayat button').forEach(x => x.classList.toggle('aktif', x === b));
  document.querySelectorAll('[data-panel]').forEach(p => p.hidden = p.dataset.panel !== b.dataset.t);
}));
</script>
JS;
require __DIR__ . '/_footer.php';
