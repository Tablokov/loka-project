<?php
require_once __DIR__ . '/../includes/member_fungsi.php';
$m = wajib_member($pdo);
$judul = 'Kartu Digital'; $aktif = 'index';
require __DIR__ . '/_header.php';

$lv = mem_level($saldo);
$ring = $pdo->prepare('SELECT COUNT(*) n, COALESCE(SUM(total),0) tot FROM transaksi WHERE pelanggan_id = ?'); $ring->execute([$m['id']]); $ring = $ring->fetch();
$vc = $pdo->prepare("SELECT * FROM voucher WHERE status_pakai='belum' AND (pelanggan_id = ? OR pelanggan_id IS NULL) AND (tgl_berakhir IS NULL OR tgl_berakhir >= CURDATE()) AND (tgl_mulai IS NULL OR tgl_mulai <= CURDATE()) ORDER BY pelanggan_id IS NULL, tgl_berakhir LIMIT 4");
$vc->execute([$m['id']]); $voucher = $vc->fetchAll();
$tr = $pdo->prepare('SELECT * FROM transaksi WHERE pelanggan_id = ? ORDER BY tanggal DESC, id DESC LIMIT 5'); $tr->execute([$m['id']]); $trx = $tr->fetchAll();
$ultah = false;
if ($m['tgl_lahir'] && date('m-d', strtotime($m['tgl_lahir'])) === date('m-d')) $ultah = true;
?>
<div class="row g-3 mb-3">
  <div class="col-lg-6">
    <div class="kartu-digital h-100">
      <span class="pola"></span>
      <div class="d-flex justify-content-between align-items-start position-relative">
        <div>
          <div class="small fw-bold" style="letter-spacing:.14em;opacity:.9">KOPI LOKA MEMBER</div>
          <div class="h3 fw-bold mt-3 mb-1"><?= e($m['nama']) ?></div>
          <div class="kode"><?= e($m['kode']) ?></div>
        </div>
        <span class="mbadge <?= strtolower($kat) ?>"><i class="bi bi-shield-fill me-1"></i><?= $kat ?></span>
      </div>
      <div class="d-flex justify-content-between align-items-end mt-4 position-relative">
        <div><div class="small" style="opacity:.85">Saldo poin</div><div class="display-6 fw-bold lh-1"><?= number_format($saldo, 0, ',', '.') ?></div>
          <div class="small mt-2" style="opacity:.85">Member sejak <?= tgl($m['tgl_daftar']) ?></div></div>
        <div class="qr-box" id="qr" role="img" aria-label="QR kode member <?= e($m['kode']) ?>"></div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="mk h-100">
      <h2 class="h6 mb-3"><i class="bi bi-graph-up-arrow me-2 text-warning"></i>Progres level</h2>
      <?php if ($lv['next']): ?>
        <div class="d-flex justify-content-between small mb-1"><span><?= $lv['kat'] ?></span><span><?= $lv['next'] ?> (<?= $lv['target'] ?> poin)</span></div>
        <div class="mprog" role="progressbar" aria-valuenow="<?= $lv['persen'] ?>" aria-valuemin="0" aria-valuemax="100"><span style="width:<?= $lv['persen'] ?>%"></span></div>
        <p class="small text-redup mt-2 mb-3">Kumpulkan <strong class="text-warning"><?= $lv['sisa'] ?> poin</strong> lagi untuk naik ke <?= $lv['next'] ?> dan dapat bonus poin <?= (MULTIPLIER_POIN[$lv['next']] - 1) * 100 ?>% di setiap pembelian.</p>
      <?php else: ?>
        <p class="mb-3"><span class="mbadge gold">Gold</span> Kamu di level tertinggi, setiap pembelian dapat bonus poin <?= (MULTIPLIER_POIN['Gold'] - 1) * 100 ?>%.</p>
      <?php endif; ?>
      <div class="row g-2">
        <div class="col-4"><div class="mstat"><div class="lbl">Transaksi</div><div class="val"><?= $ring['n'] ?></div></div></div>
        <div class="col-4"><div class="mstat"><div class="lbl">Voucher</div><div class="val"><?= count($voucher) ?></div></div></div>
        <div class="col-4"><div class="mstat emas"><div class="lbl">Menu favorit</div><div class="small fw-bold mt-2"><?= e($m['preferensi'] ?: 'Belum diisi') ?></div></div></div>
      </div>
    </div>
  </div>
</div>

<?php if ($ultah): ?><div class="alert alert-warning"><i class="bi bi-cake2 me-2"></i><strong>Selamat ulang tahun, <?= e(strtok($m['nama'], ' ')) ?>!</strong> Cek voucher spesialmu di bawah atau tanyakan ke kasir.</div><?php endif; ?>

<div class="row g-3">
  <div class="col-lg-6"><div class="mk h-100">
    <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h6 mb-0"><i class="bi bi-ticket-perforated me-2 text-warning"></i>Voucher aktif</h2><a class="small" href="tukar.php#voucher">Lihat semua</a></div>
    <?php foreach ($voucher as $v): ?>
      <div class="voucher-tiket mb-2 d-flex justify-content-between align-items-center gap-2">
        <div><code><?= e($v['kode']) ?></code><div class="small"><?= e($v['nama']) ?></div><div class="small text-redup">s.d. <?= tgl($v['tgl_berakhir']) ?><?= $v['pelanggan_id'] ? ' &middot; khusus kamu' : '' ?></div></div>
        <span class="fw-bold text-warning fs-5"><?= $v['tipe_diskon'] === 'persen' ? (int)$v['nilai'] . '%' : rupiah($v['nilai']) ?></span>
      </div>
    <?php endforeach; ?>
    <?php if (!$voucher): ?><div class="mkosong"><i class="bi bi-ticket-perforated"></i>Belum ada voucher aktif.</div><?php endif; ?>
    <p class="small text-redup mb-0 mt-2">Tunjukkan kode voucher ke kasir saat membayar.</p>
  </div></div>
  <div class="col-lg-6"><div class="mk h-100">
    <div class="d-flex justify-content-between align-items-center mb-2"><h2 class="h6 mb-0"><i class="bi bi-receipt me-2 text-warning"></i>Transaksi terakhir</h2><a class="small" href="riwayat.php">Semua riwayat</a></div>
    <?php if ($trx): ?><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Tanggal</th><th class="text-end">Total</th><th class="text-end">Poin</th></tr></thead><tbody>
      <?php foreach ($trx as $t): ?><tr><td><?= tgl($t['tanggal']) ?></td><td class="text-end"><?= rupiah($t['total']) ?></td><td class="text-end text-warning">+<?= $t['poin_didapat'] ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php else: ?><div class="mkosong"><i class="bi bi-cup-hot"></i>Belum ada transaksi. Yuk pesan kopi pertamamu!</div><?php endif; ?>
  </div></div>
</div>
<?php
$kode = json_encode($m['kode']);
$skrip_tambahan = <<<JS
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>new QRCode(document.getElementById('qr'), {text: $kode, width: 96, height: 96, colorDark: '#000000', colorLight: '#ffffff'});</script>
JS;
require __DIR__ . '/_footer.php';
