<?php
$judul = 'Loyalty - Promo ulang tahun';
require __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/fungsi_loyalty.php';
const DISKON_ULTAH = 20;   // persen
const BERLAKU_HARI = 14;   // hari sejak voucher dibuat
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $s = $pdo->prepare('SELECT * FROM pelanggan WHERE id = ? AND tgl_lahir IS NOT NULL'); $s->execute([(int)($_POST['id'] ?? 0)]); $p = $s->fetch();
    if (!$p) flash('Pelanggan tidak ditemukan atau belum punya tanggal lahir.', 'danger');
    else {
        $kode = 'BDAY-' . $p['kode'] . '-' . date('Y');
        $ada = $pdo->prepare('SELECT COUNT(*) FROM voucher WHERE kode = ?'); $ada->execute([$kode]);
        if ($ada->fetchColumn()) flash('Voucher ulang tahun tahun ini sudah dibuat untuk ' . $p['nama'] . '.', 'warning');
        else {
            $pdo->prepare("INSERT INTO voucher (kode,nama,tipe_diskon,nilai,tgl_mulai,tgl_berakhir,pelanggan_id) VALUES (?,?, 'persen', ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL ? DAY), ?)")
                ->execute([$kode, 'Voucher Ulang Tahun ' . $p['nama'], DISKON_ULTAH, BERLAKU_HARI, $p['id']]);
            flash("Voucher $kode (diskon " . DISKON_ULTAH . '%) dibuat untuk ' . $p['nama'] . '.');
        }
    }
    redirect('loyalty/promo.php');
}
$rows = $pdo->query('SELECT * FROM pelanggan WHERE tgl_lahir IS NOT NULL')->fetchAll();
$hariIni = new DateTime('today'); $daftar = [];
foreach ($rows as $p) {
    $lahir = new DateTime($p['tgl_lahir']);
    $next = new DateTime($hariIni->format('Y') . '-' . $lahir->format('m-d'));
    if ($next < $hariIni) $next->modify('+1 year');
    $selisih = (int)$hariIni->diff($next)->days;
    if ($selisih <= 30) $daftar[] = $p + ['selisih' => $selisih, 'next' => $next];
}
usort($daftar, fn($a, $b) => $a['selisih'] <=> $b['selisih']);
$sudah = $pdo->query("SELECT kode FROM voucher WHERE kode LIKE 'BDAY-%-" . date('Y') . "'")->fetchAll(PDO::FETCH_COLUMN);
include '_tabs.php';
?>
<p class="text-muted small">Pelanggan yang berulang tahun dalam 30 hari ke depan. Voucher personal diskon <?= DISKON_ULTAH ?>%, berlaku <?= BERLAKU_HARI ?> hari sejak dibuat.</p>
<div class="kartu p-0"><div class="table-responsive"><table class="table align-middle mb-0">
  <thead><tr><th>Pelanggan</th><th>Ulang tahun</th><th>Hitung mundur</th><th></th></tr></thead><tbody>
  <?php foreach ($daftar as $d): $kode = 'BDAY-' . $d['kode'] . '-' . date('Y'); ?>
    <tr><td><?= e($d['nama']) ?> <span class="text-muted small"><?= e($d['kode']) ?></span></td><td><?= $d['next']->format('d/m/Y') ?></td>
      <td><?= $d['selisih'] === 0 ? '<strong>Hari ini!</strong>' : $d['selisih'] . ' hari lagi' ?></td>
      <td><?php if (in_array($kode, $sudah, true)): ?><span class="badge text-bg-success">Voucher sudah dibuat</span>
        <?php else: ?><form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="id" value="<?= $d['id'] ?>"><button class="btn btn-sm btn-loka">Buat voucher</button></form><?php endif; ?></td></tr>
  <?php endforeach; ?>
  <?php if (!$daftar): ?><tr><td colspan="4" class="text-center text-muted py-4">Tidak ada yang berulang tahun dalam 30 hari ke depan.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/../includes/footer.php';
