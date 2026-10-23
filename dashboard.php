<?php
$judul = 'Dashboard';
require __DIR__ . '/includes/header.php';

$total_pelanggan = $pdo->query('SELECT COUNT(*) FROM pelanggan')->fetchColumn();
$pelanggan_baru  = $pdo->query('SELECT COUNT(*) FROM pelanggan WHERE tgl_daftar >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)')->fetchColumn();
$komplain_buka   = $pdo->query("SELECT COUNT(*) FROM komplain WHERE status <> 'Selesai'")->fetchColumn();
$poin_beredar    = $pdo->query("SELECT COALESCE(SUM(CASE jenis WHEN 'masuk' THEN jumlah ELSE -jumlah END),0) FROM poin_pelanggan")->fetchColumn();
$omzet_bulan     = $pdo->query('SELECT COALESCE(SUM(total),0) FROM transaksi WHERE tanggal >= DATE_FORMAT(CURDATE(), "%Y-%m-01")')->fetchColumn();

// Sebaran kategori loyalitas (dihitung dari total poin tiap pelanggan)
$rows = $pdo->query("SELECT p.id, COALESCE(SUM(CASE pp.jenis WHEN 'masuk' THEN pp.jumlah ELSE -pp.jumlah END),0) poin
                     FROM pelanggan p LEFT JOIN poin_pelanggan pp ON pp.pelanggan_id = p.id GROUP BY p.id")->fetchAll();
$loyal = ['Bronze'=>0,'Silver'=>0,'Gold'=>0];
foreach ($rows as $r) $loyal[kategori_loyalitas($r['poin'])]++;

// Transaksi 14 hari terakhir
$hari = [];
for ($i = 13; $i >= 0; $i--) $hari[date('Y-m-d', strtotime("-$i day"))] = 0;
$st = $pdo->query('SELECT tanggal, COUNT(*) n FROM transaksi WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY tanggal');
foreach ($st as $r) $hari[$r['tanggal']] = (int)$r['n'];

// Pelanggan terbaru
$terbaru = $pdo->query('SELECT kode, nama, tgl_daftar, preferensi FROM pelanggan ORDER BY tgl_daftar DESC, id DESC LIMIT 5')->fetchAll();
?>
<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3"><div class="kartu stat utama"><div>Total pelanggan</div><div class="angka"><?= $total_pelanggan ?></div></div></div>
  <div class="col-6 col-xl-3"><div class="kartu stat"><div class="text-muted">Pelanggan baru (30 hari)</div><div class="angka"><?= $pelanggan_baru ?></div></div></div>
  <div class="col-6 col-xl-3"><div class="kartu stat"><div class="text-muted">Komplain terbuka</div><div class="angka"><?= $komplain_buka ?></div></div></div>
  <div class="col-6 col-xl-3"><div class="kartu stat"><div class="text-muted">Poin beredar</div><div class="angka"><?= number_format($poin_beredar,0,',','.') ?></div></div></div>
</div>
<div class="row g-3">
  <div class="col-lg-7"><div class="kartu">
    <h2 class="h6">Jumlah transaksi per hari (14 hari terakhir)</h2>
    <canvas id="grafikTransaksi" height="130"></canvas>
    <div class="small text-muted mt-2">Omzet bulan ini: <strong><?= rupiah($omzet_bulan) ?></strong></div>
  </div></div>
  <div class="col-lg-5"><div class="kartu">
    <h2 class="h6">Kategori loyalitas pelanggan</h2>
    <canvas id="grafikLoyalitas" height="200"></canvas>
  </div></div>
  <div class="col-12"><div class="kartu">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h2 class="h6 mb-0">Pelanggan terbaru</h2>
      <a class="btn btn-sm btn-outline-loka" href="<?= url('pelanggan/index.php') ?>">Lihat semua</a>
    </div>
    <div class="table-responsive"><table class="table table-sm align-middle mb-0">
      <thead><tr><th>Kode</th><th>Nama</th><th>Menu favorit</th><th>Terdaftar</th></tr></thead>
      <tbody><?php foreach ($terbaru as $p): ?>
        <tr><td><?= e($p['kode']) ?></td><td><?= e($p['nama']) ?></td><td><?= e($p['preferensi']) ?></td><td><?= tgl($p['tgl_daftar']) ?></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
  </div></div>
</div>
<?php
$labelHari = json_encode(array_map(fn($d) => date('d/m', strtotime($d)), array_keys($hari)));
$dataHari  = json_encode(array_values($hari));
$labelL = json_encode(array_keys($loyal));
$dataL  = json_encode(array_values($loyal));
$skrip_tambahan = <<<JS
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('grafikTransaksi'), {
  type: 'bar',
  data: { labels: $labelHari, datasets: [{ label: 'Transaksi', data: $dataHari, backgroundColor: '#FF5A0A', borderRadius: 4 }] },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});
new Chart(document.getElementById('grafikLoyalitas'), {
  type: 'doughnut',
  data: { labels: $labelL, datasets: [{ data: $dataL, backgroundColor: ['#B87333', '#8A9199', '#D9A400'] }] },
  options: { plugins: { legend: { position: 'bottom' } } }
});
</script>
JS;
require __DIR__ . '/includes/footer.php';
