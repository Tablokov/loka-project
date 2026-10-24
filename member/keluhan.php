<?php
require_once __DIR__ . '/../includes/member_fungsi.php';
$m = wajib_member($pdo);
$kategoriList = ['Produk' => 'Kualitas produk (rasa, takaran, suhu)', 'Pelayanan' => 'Pelayanan & kecepatan', 'Kebersihan' => 'Kebersihan kedai', 'Lainnya' => 'Lainnya (poin, voucher, dll.)'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mem_cek_csrf();
    $kat = $_POST['kategori'] ?? ''; $pri = $_POST['prioritas'] ?? 'Sedang';
    $des = trim($_POST['deskripsi'] ?? ''); $tid = (int)($_POST['transaksi_id'] ?? 0);
    $hariIni = $pdo->prepare('SELECT COUNT(*) FROM komplain WHERE pelanggan_id = ? AND tanggal = CURDATE()'); $hariIni->execute([$m['id']]);
    if (!isset($kategoriList[$kat]) || !in_array($pri, ['Rendah','Sedang','Tinggi'], true)) flash('Pilih kategori dan tingkat urgensi.', 'danger');
    elseif (strlen($des) < 10) flash('Ceritakan keluhanmu dengan lebih jelas (minimal 10 karakter).', 'danger');
    elseif (strlen($des) > 1500) flash('Deskripsi terlalu panjang (maksimal 1500 karakter).', 'danger');
    elseif ($hariIni->fetchColumn() >= 5) flash('Kamu sudah mengirim 5 keluhan hari ini. Coba lagi besok atau hubungi kami lewat WhatsApp.', 'warning');
    else {
        if ($tid) {
            $t = $pdo->prepare('SELECT tanggal, total FROM transaksi WHERE id = ? AND pelanggan_id = ?'); $t->execute([$tid, $m['id']]); $t = $t->fetch();
            if ($t) $des .= "\n[Transaksi terkait: " . tgl($t['tanggal']) . ', ' . rupiah($t['total']) . ']';
        }
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO komplain (pelanggan_id,tanggal,kategori,deskripsi,status,prioritas) VALUES (?,CURDATE(),?,?,'Baru',?)")->execute([$m['id'], $kat, $des, $pri]);
        $kid = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO riwayat_komplain (komplain_id,tanggal,tindakan,petugas_id,status_baru) VALUES (?,NOW(),'Keluhan diajukan oleh pelanggan',NULL,'Baru')")->execute([$kid]);
        $pdo->commit();
        flash('Keluhanmu sudah kami terima. Pantau statusnya di halaman ini.');
    }
    redirect('member/keluhan.php');
}
$judul = 'Layanan Keluhan'; $aktif = 'keluhan';
require __DIR__ . '/_header.php';

$st = $pdo->prepare('SELECT * FROM komplain WHERE pelanggan_id = ? ORDER BY id DESC'); $st->execute([$m['id']]); $tiket = $st->fetchAll();
$riw = [];
if ($tiket) {
    $ids = implode(',', array_map('intval', array_column($tiket, 'id')));
    foreach ($pdo->query("SELECT * FROM riwayat_komplain WHERE komplain_id IN ($ids) ORDER BY tanggal DESC, id DESC") as $r) $riw[$r['komplain_id']][] = $r;
}
$nTotal = count($tiket);
$nSelesai = count(array_filter($tiket, fn($t) => $t['status'] === 'Selesai'));
$nJalan = $nTotal - $nSelesai;
$trx = $pdo->prepare('SELECT id, tanggal, total FROM transaksi WHERE pelanggan_id = ? ORDER BY tanggal DESC, id DESC LIMIT 10'); $trx->execute([$m['id']]); $trx = $trx->fetchAll();
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
  <div><h1 class="mjudul"><i class="bi bi-headset text-warning me-2"></i>Layanan Pengaduan &amp; Aspirasi</h1>
    <p class="msub">Kenyamananmu prioritas kami. Sampaikan keluhan soal rasa, pelayanan barista, atau kendala transaksi dan poin.</p></div>
  <button class="mbtn" data-bs-toggle="modal" data-bs-target="#modalKeluhan"><i class="bi bi-pencil-square"></i>Tulis Keluhan Baru</button>
</div>
<div class="row g-3 mb-3">
  <div class="col-4"><div class="mstat"><div class="lbl">Total laporan</div><div class="val"><?= $nTotal ?></div></div></div>
  <div class="col-4"><div class="mstat emas"><div class="lbl">Sedang ditangani</div><div class="val"><?= $nJalan ?></div></div></div>
  <div class="col-4"><div class="mstat"><div class="lbl">Solusi selesai</div><div class="val" style="color:var(--hijau)"><?= $nSelesai ?></div></div></div>
</div>
<div class="row g-3">
  <div class="col-lg-8"><div class="mk">
    <h2 class="h6 mb-3"><i class="bi bi-ticket-detailed text-warning me-2"></i>Riwayat &amp; status tiket keluhan saya</h2>
    <div class="mtab mb-3" id="filterTiket">
      <button type="button" class="aktif" data-f="semua">Semua (<?= $nTotal ?>)</button>
      <button type="button" data-f="jalan">Berjalan (<?= $nJalan ?>)</button>
      <button type="button" data-f="selesai">Selesai (<?= $nSelesai ?>)</button>
    </div>
    <?php foreach ($tiket as $t): [$sl, $sk] = mem_label_status($t['status']); ?>
      <div class="tiket" data-status="<?= $t['status'] === 'Selesai' ? 'selesai' : 'jalan' ?>">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
          <div><strong><?= e($t['kategori']) ?></strong> <span class="mbadge <?= strtolower($t['prioritas']) ?>"><?= e($t['prioritas']) ?></span>
            <div class="small text-redup">Tiket #<?= $t['id'] ?> &middot; <?= tgl($t['tanggal']) ?></div></div>
          <span class="mbadge <?= $sk ?>"><?= $sl ?></span>
        </div>
        <p class="mb-2 small"><?= nl2br(e($t['deskripsi'])) ?></p>
        <a class="small" data-bs-toggle="collapse" href="#rw<?= $t['id'] ?>" role="button" aria-expanded="false">Lihat tindak lanjut (<?= count($riw[$t['id']] ?? []) ?>)</a>
        <div class="collapse mt-2" id="rw<?= $t['id'] ?>">
          <?php foreach ($riw[$t['id']] ?? [] as $r): ?>
            <div class="small border-start border-2 ps-2 mb-2" style="border-color:var(--loka)!important">
              <div><?= e($r['tindakan']) ?> <span class="mbadge <?= mem_label_status($r['status_baru'])[1] ?>"><?= mem_label_status($r['status_baru'])[0] ?></span></div>
              <div class="text-redup"><?= date('d/m/Y H:i', strtotime($r['tanggal'])) ?> &middot; <?= $r['petugas_id'] ? 'Tim Kopi LOKA' : 'Kamu' ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
    <div class="mkosong" id="kosongTiket" <?= $tiket ? 'hidden' : '' ?>><i class="bi bi-emoji-smile"></i><strong class="d-block text-white">Belum ada keluhan</strong>
      Kamu belum pernah mengajukan keluhan atau masukan. Kalau ada kendala, klik tombol di bawah.<div class="mt-3"><button class="mbtn" data-bs-toggle="modal" data-bs-target="#modalKeluhan"><i class="bi bi-plus-lg"></i>Ajukan Keluhan Pertama</button></div></div>
  </div></div>
  <div class="col-lg-4">
    <?php if (LOKA_WA !== ''): ?>
    <div class="mk mb-3"><h2 class="h6"><i class="bi bi-whatsapp me-2" style="color:var(--hijau)"></i>Butuh respon cepat?</h2>
      <p class="small text-redup">Kalau ada masalah darurat di kedai (pesanan salah atau tertukar), hubungi kami lewat WhatsApp.</p>
      <a class="mbtn wa w-100" href="https://wa.me/<?= e(LOKA_WA) ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i>Chat WhatsApp</a></div>
    <?php endif; ?>
    <div class="mk"><h2 class="h6 mb-2"><i class="bi bi-question-circle text-warning me-2"></i>Tanya jawab (FAQ)</h2>
      <div class="accordion accordion-flush" id="faq">
        <?php foreach ([['Berapa lama keluhan diproses?', 'Tim kami menindaklanjuti secepatnya. Kamu bisa memantau status dan catatan tindak lanjut langsung di halaman ini.'],
                        ['Bagaimana jika poin belum masuk?', 'Poin masuk otomatis setelah transaksi dicatat kasir (Rp10.000 = 1 poin). Jika belum muncul, ajukan keluhan kategori "Lainnya" dan pilih transaksi terkait.'],
                        ['Bagaimana mendapat kompensasi voucher?', 'Untuk keluhan yang terbukti, tim kami dapat memberi voucher. Voucher yang kamu terima akan tampil di menu Tukar Poin > Voucher Saya.']] as $i => [$q, $a]): ?>
          <div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f<?= $i ?>"><?= e($q) ?></button></h3>
            <div id="f<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#faq"><div class="accordion-body small text-redup pt-0"><?= e($a) ?></div></div></div>
        <?php endforeach; ?>
      </div></div>
  </div>
</div>

<div class="modal fade" id="modalKeluhan" tabindex="-1" aria-labelledby="judulModal" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><form method="post" class="modal-content">
  <input type="hidden" name="csrf" value="<?= csrf() ?>">
  <div class="modal-header"><div><h2 class="modal-title h5 fw-bold" id="judulModal">Formulir Layanan Pengaduan &amp; Bantuan</h2><small style="color:var(--emas)">Akun member: <?= e($m['nama']) ?> (<?= e($m['kode']) ?>)</small></div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
  <div class="modal-body"><div class="row g-3">
    <div class="col-md-6"><label class="form-label" for="kategori">Kategori kendala *</label><select class="form-select" id="kategori" name="kategori" required>
      <?php foreach ($kategoriList as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-label" for="prioritas">Tingkat urgensi *</label><select class="form-select" id="prioritas" name="prioritas">
      <option value="Rendah">Rendah (masukan / saran)</option><option value="Sedang" selected>Sedang (kendala biasa)</option><option value="Tinggi">Tinggi (mendesak)</option></select></div>
    <div class="col-12"><label class="form-label" for="transaksi_id">Transaksi terkait (opsional)</label><select class="form-select" id="transaksi_id" name="transaksi_id">
      <option value="0">Tidak terkait transaksi tertentu</option>
      <?php foreach ($trx as $t): ?><option value="<?= $t['id'] ?>"><?= tgl($t['tanggal']) ?> &middot; <?= rupiah($t['total']) ?></option><?php endforeach; ?></select></div>
    <div class="col-12"><label class="form-label" for="deskripsi">Deskripsi keluhan / masukan *</label>
      <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5" minlength="10" maxlength="1500" required placeholder="Ceritakan kendalamu dengan jelas, misalnya: kopi susu terlalu manis, menunggu terlalu lama, poin belum bertambah..."></textarea>
      <div class="form-text">Informasi yang lengkap mempercepat penanganan oleh tim kami.</div></div>
  </div></div>
  <div class="modal-footer"><button type="button" class="mbtn gelap" data-bs-dismiss="modal">Batal</button><button class="mbtn"><i class="bi bi-send"></i>Kirimkan Keluhan Saya</button></div>
</form></div></div>
<?php
$skrip_tambahan = <<<'JS'
<script>
document.querySelectorAll('#filterTiket button').forEach(b => b.addEventListener('click', () => {
  document.querySelectorAll('#filterTiket button').forEach(x => x.classList.remove('aktif')); b.classList.add('aktif');
  let n = 0; document.querySelectorAll('.tiket').forEach(t => { const tampil = b.dataset.f === 'semua' || t.dataset.status === b.dataset.f; t.hidden = !tampil; if (tampil) n++; });
  const k = document.getElementById('kosongTiket'); if (k) k.hidden = n > 0;
}));
</script>
JS;
require __DIR__ . '/_footer.php';
