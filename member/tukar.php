<?php
require_once __DIR__ . '/../includes/member_fungsi.php';
$m = wajib_member($pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $rid = (int)($_POST['reward_id'] ?? 0);
    $pdo->beginTransaction();
    try {
        $r = $pdo->prepare('SELECT * FROM reward WHERE id = ? FOR UPDATE'); $r->execute([$rid]); $r = $r->fetch();
        if (!$r) throw new Exception('Reward tidak ditemukan.');
        if ($r['status'] !== 'aktif') throw new Exception('Reward ini sedang tidak tersedia.');
        if ($r['stok'] < 1) throw new Exception('Maaf, stok reward ini habis.');
        $saldo = total_poin($pdo, $m['id']);
        if ($saldo < $r['poin_dibutuhkan']) throw new Exception('Poin kamu belum cukup untuk reward ini.');
        $pdo->prepare("INSERT INTO penukaran_reward (pelanggan_id,reward_id,tanggal,status) VALUES (?,?,CURDATE(),'diklaim')")->execute([$m['id'], $rid]);
        $pid = (int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO poin_pelanggan (pelanggan_id,jenis,jumlah,keterangan,tanggal) VALUES (?, 'keluar', ?, ?, CURDATE())")->execute([$m['id'], $r['poin_dibutuhkan'], 'Tukar reward: ' . $r['nama']]);
        $pdo->prepare('UPDATE reward SET stok = stok - 1 WHERE id = ?')->execute([$rid]);
        $pdo->commit();
        flash('Berhasil menukar ' . $r['nama'] . '! Kode klaim: RW-' . str_pad($pid, 5, '0', STR_PAD_LEFT) . '. Tunjukkan kode ini ke kasir.');
    } catch (Exception $ex) { $pdo->rollBack(); flash($ex->getMessage(), 'danger'); }
    redirect('member/tukar.php');
}
$judul = 'Tukar Poin'; $aktif = 'tukar';
require __DIR__ . '/_header.php';
$lv = mem_level($saldo);
$nTrx = (int)$pdo->query('SELECT COUNT(*) FROM transaksi WHERE pelanggan_id = ' . (int)$m['id'])->fetchColumn();
$rewards = $pdo->query("SELECT * FROM reward WHERE status = 'aktif' ORDER BY poin_dibutuhkan")->fetchAll();
$vq = $pdo->prepare("SELECT * FROM voucher WHERE pelanggan_id = ? OR (pelanggan_id IS NULL AND status_pakai='belum' AND (tgl_berakhir IS NULL OR tgl_berakhir >= CURDATE())) ORDER BY pelanggan_id IS NULL, id DESC LIMIT 30");
$vq->execute([$m['id']]); $vouchers = $vq->fetchAll();
$hq = $pdo->prepare('SELECT t.id, t.tanggal, t.status, r.nama, r.poin_dibutuhkan FROM penukaran_reward t JOIN reward r ON r.id = t.reward_id WHERE t.pelanggan_id = ? ORDER BY t.id DESC LIMIT 20');
$hq->execute([$m['id']]); $hist = $hq->fetchAll();
?>
<div class="mk mb-3"><div class="row g-3 align-items-center">
  <div class="col-lg-8"><span class="mbadge" style="background:#7a1f2b;color:#ffd1d6">PUSAT HADIAH &amp; LOYALTI</span>
    <h1 class="mjudul mt-2">Klaim Hadiah &amp; Tukarkan Poinmu</h1>
    <p class="msub">Kumpulkan poin dari setiap pembelian (Rp10.000 = 1 poin) lalu tukarkan dengan minuman gratis atau potongan harga di Kopi LOKA.</p></div>
  <div class="col-lg-4"><div class="row g-2">
    <div class="col-6"><div class="mstat emas"><div class="lbl">Total transaksi</div><div class="val"><?= $nTrx ?></div></div></div>
    <div class="col-6"><div class="mstat emas"><div class="lbl">Saldo poin</div><div class="val"><?= number_format($saldo, 0, ',', '.') ?></div><span class="mbadge <?= strtolower($kat) ?>"><?= $kat ?></span></div></div>
  </div></div>
</div></div>

<div class="mtab mb-3" id="tabTukar">
  <button type="button" class="aktif" data-t="katalog"><i class="bi bi-gift me-1"></i>Katalog Reward (<?= $saldo ?> Pts)</button>
  <button type="button" data-t="voucher"><i class="bi bi-ticket-perforated me-1"></i>Voucher Saya</button>
  <button type="button" data-t="riwayat"><i class="bi bi-clock-history me-1"></i>Riwayat Penukaran</button>
</div>

<section data-panel="katalog">
  <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0"><i class="bi bi-gift text-warning me-2"></i>Katalog Hadiah Poin</h2><span class="rw-tag"><?= count($rewards) ?> pilihan hadiah</span></div>
  <div class="row g-3">
  <?php foreach ($rewards as $r): $kurang = $r['poin_dibutuhkan'] - $saldo; $habis = $r['stok'] < 1; ?>
    <div class="col-md-6 col-xl-4"><div class="rw-kartu">
      <div class="d-flex justify-content-between mb-2"><span class="rw-tag"><i class="bi bi-cup-hot me-1"></i>Reward</span><span class="small text-warning fw-bold">Stok: <?= (int)$r['stok'] ?></span></div>
      <h3 class="h5 fw-bold"><?= e($r['nama']) ?></h3>
      <div class="mt-auto pt-3 border-top border-secondary-subtle">
        <div class="d-flex justify-content-between align-items-center mb-3"><div><div class="small text-redup">Dibutuhkan</div><div class="poin"><?= number_format($r['poin_dibutuhkan'], 0, ',', '.') ?> Pts</div></div>
          <?php if ($kurang > 0 && !$habis): ?><span class="kurang">Kurang <?= $kurang ?> Pts</span><?php endif; ?></div>
        <?php if ($habis): ?><button class="mbtn mati w-100" disabled><i class="bi bi-slash-circle"></i>Stok habis</button>
        <?php elseif ($kurang > 0): ?><button class="mbtn mati w-100" disabled><i class="bi bi-lock"></i>Poin belum cukup</button>
        <?php else: ?><form method="post" onsubmit="return confirm('Tukar <?= (int)$r['poin_dibutuhkan'] ?> poin dengan <?= e(addslashes($r['nama'])) ?>?')"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="reward_id" value="<?= $r['id'] ?>"><button class="mbtn w-100"><i class="bi bi-gift"></i>Tukar sekarang</button></form><?php endif; ?>
      </div></div></div>
  <?php endforeach; ?>
  <?php if (!$rewards): ?><div class="col-12"><div class="mk mkosong"><i class="bi bi-gift"></i>Belum ada reward yang tersedia.</div></div><?php endif; ?>
  </div>
</section>

<section data-panel="voucher" id="voucher" hidden><div class="mk">
  <h2 class="h5 mb-3"><i class="bi bi-ticket-perforated text-warning me-2"></i>Voucher Saya</h2>
  <div class="row g-3">
  <?php foreach ($vouchers as $v):
      $st = $v['status_pakai'] === 'terpakai' ? ['Terpakai','proses'] : (($v['tgl_berakhir'] && $v['tgl_berakhir'] < date('Y-m-d')) ? ['Kedaluwarsa','tinggi'] : (($v['tgl_mulai'] && $v['tgl_mulai'] > date('Y-m-d')) ? ['Belum berlaku','baru'] : ['Aktif','selesai'])); ?>
    <div class="col-md-6"><div class="voucher-tiket h-100 <?= $st[0] === 'Aktif' ? '' : 'opacity-50' ?>">
      <div class="d-flex justify-content-between"><code><?= e($v['kode']) ?></code><span class="mbadge <?= $st[1] ?>"><?= $st[0] ?></span></div>
      <div class="fw-semibold"><?= e($v['nama']) ?></div>
      <div class="d-flex justify-content-between small text-redup"><span>s.d. <?= tgl($v['tgl_berakhir']) ?><?= $v['pelanggan_id'] ? ' &middot; khusus kamu' : '' ?></span>
        <strong class="text-warning fs-6"><?= $v['tipe_diskon'] === 'persen' ? (int)$v['nilai'] . '%' : rupiah($v['nilai']) ?></strong></div>
    </div></div>
  <?php endforeach; ?>
  <?php if (!$vouchers): ?><div class="col-12 mkosong"><i class="bi bi-ticket-perforated"></i>Belum ada voucher.</div><?php endif; ?>
  </div>
  <p class="small text-redup mt-3 mb-0">Tunjukkan kode voucher ke kasir. Kasir yang menandai voucher terpakai.</p>
</div></section>

<section data-panel="riwayat" hidden><div class="mk">
  <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0"><i class="bi bi-clock-history text-warning me-2"></i>Riwayat Penukaran Poin Saya</h2><span class="mbadge"><?= count($hist) ?> penukaran</span></div>
  <?php if ($hist): ?><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Kode klaim</th><th>Tanggal</th><th>Reward</th><th class="text-end">Poin</th></tr></thead><tbody>
    <?php foreach ($hist as $h): ?><tr><td><code class="text-warning">RW-<?= str_pad($h['id'], 5, '0', STR_PAD_LEFT) ?></code></td><td><?= tgl($h['tanggal']) ?></td><td><?= e($h['nama']) ?></td><td class="text-end">-<?= $h['poin_dibutuhkan'] ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <?php else: ?><div class="mkosong">Kamu belum pernah menukarkan poin. Kumpulkan poin dari setiap pesanan kopimu!</div><?php endif; ?>
</div></section>
<?php
$skrip_tambahan = <<<'JS'
<script>
function tampilTab(t){
  document.querySelectorAll('#tabTukar button').forEach(b => b.classList.toggle('aktif', b.dataset.t === t));
  document.querySelectorAll('[data-panel]').forEach(p => p.hidden = p.dataset.panel !== t);
}
document.querySelectorAll('#tabTukar button').forEach(b => b.addEventListener('click', () => tampilTab(b.dataset.t)));
if (location.hash === '#voucher') tampilTab('voucher');
</script>
JS;
require __DIR__ . '/_footer.php';
