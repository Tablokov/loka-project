<?php
$judul = 'Loyalty - Voucher';
require __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/fungsi_loyalty.php';
$admin = $_SESSION['user']['role'] === 'admin';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'pakai') {
        $u = $pdo->prepare("UPDATE voucher SET status_pakai='terpakai' WHERE id = ? AND status_pakai='belum' AND (tgl_berakhir IS NULL OR tgl_berakhir >= CURDATE())");
        $u->execute([(int)$_POST['id']]);
        flash($u->rowCount() ? 'Voucher ditandai terpakai.' : 'Voucher tidak bisa dipakai (sudah terpakai atau kedaluwarsa).', $u->rowCount() ? 'success' : 'danger');
    } elseif ($aksi === 'tambah' && $admin) {
        $kode = strtoupper(trim($_POST['kode'] ?? '')); $nama = trim($_POST['nama'] ?? '');
        $tipe = $_POST['tipe'] ?? ''; $nilai = (int)($_POST['nilai'] ?? 0);
        $mulai = $_POST['tgl_mulai'] ?? ''; $akhir = $_POST['tgl_berakhir'] ?? '';
        $pid = (int)($_POST['pelanggan_id'] ?? 0) ?: null;
        $ada = $pdo->prepare('SELECT COUNT(*) FROM voucher WHERE kode = ?'); $ada->execute([$kode]);
        if ($kode === '' || $nama === '' || !in_array($tipe, ['persen','nominal'], true) || $nilai <= 0 || !$mulai || !$akhir) flash('Lengkapi semua kolom voucher.', 'danger');
        elseif ($tipe === 'persen' && $nilai > 100) flash('Diskon persen maksimal 100.', 'danger');
        elseif ($akhir < $mulai) flash('Tanggal berakhir tidak boleh sebelum tanggal mulai.', 'danger');
        elseif ($ada->fetchColumn()) flash('Kode voucher sudah dipakai.', 'danger');
        else { $pdo->prepare('INSERT INTO voucher (kode,nama,tipe_diskon,nilai,tgl_mulai,tgl_berakhir,pelanggan_id) VALUES (?,?,?,?,?,?,?)')->execute([$kode,$nama,$tipe,$nilai,$mulai,$akhir,$pid]); flash("Voucher $kode dibuat."); }
    }
    redirect('loyalty/voucher.php');
}
$data = $pdo->query('SELECT v.*, p.nama pel FROM voucher v LEFT JOIN pelanggan p ON p.id = v.pelanggan_id ORDER BY v.id DESC LIMIT 100')->fetchAll();
$pel = $pdo->query('SELECT id, kode, nama FROM pelanggan ORDER BY nama')->fetchAll();
include '_tabs.php';
?>
<div class="row g-3">
  <div class="<?= $admin ? 'col-lg-8' : 'col-12' ?>"><div class="kartu p-0"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Kode</th><th>Voucher</th><th>Diskon</th><th>Berlaku</th><th>Untuk</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($data as $v): [$sl, $sw] = loy_status_voucher($v); ?>
      <tr><td><code><?= e($v['kode']) ?></code></td><td><?= e($v['nama']) ?></td><td><?= loy_teks_diskon($v) ?></td>
        <td class="small"><?= tgl($v['tgl_mulai']) ?> - <?= tgl($v['tgl_berakhir']) ?></td><td><?= $v['pel'] ? e($v['pel']) : 'Umum' ?></td>
        <td><span class="badge text-bg-<?= $sw ?>"><?= $sl ?></span></td>
        <td><?php if ($sl === 'Aktif'): ?><form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="aksi" value="pakai"><input type="hidden" name="id" value="<?= $v['id'] ?>">
          <button class="btn btn-sm btn-outline-loka">Tandai terpakai</button></form><?php endif; ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$data): ?><tr><td colspan="7" class="text-center text-muted py-4">Belum ada voucher.</td></tr><?php endif; ?>
    </tbody></table></div></div></div>
  <?php if ($admin): ?><div class="col-lg-4"><form method="post" class="kartu">
    <h2 class="h6">Buat voucher</h2><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="aksi" value="tambah">
    <label class="form-label" for="kode">Kode</label><input class="form-control mb-2" id="kode" name="kode" required placeholder="WELCOME10">
    <label class="form-label" for="nama">Nama voucher</label><input class="form-control mb-2" id="nama" name="nama" required>
    <div class="row g-2 mb-2"><div class="col-6"><label class="form-label" for="tipe">Tipe</label><select class="form-select" id="tipe" name="tipe"><option value="persen">Persen</option><option value="nominal">Nominal (Rp)</option></select></div>
      <div class="col-6"><label class="form-label" for="nilai">Nilai</label><input class="form-control" id="nilai" type="number" min="1" name="nilai" required></div></div>
    <div class="row g-2 mb-2"><div class="col-6"><label class="form-label" for="tgl_mulai">Mulai</label><input class="form-control" id="tgl_mulai" type="date" name="tgl_mulai" value="<?= date('Y-m-d') ?>" required></div>
      <div class="col-6"><label class="form-label" for="tgl_berakhir">Berakhir</label><input class="form-control" id="tgl_berakhir" type="date" name="tgl_berakhir" value="<?= date('Y-m-d', strtotime('+30 day')) ?>" required></div></div>
    <label class="form-label" for="pelanggan_id">Khusus pelanggan (opsional)</label>
    <select class="form-select mb-3" id="pelanggan_id" name="pelanggan_id"><option value="">Umum (semua pelanggan)</option>
      <?php foreach ($pel as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['kode'] . ' - ' . $p['nama']) ?></option><?php endforeach; ?></select>
    <button class="btn btn-loka w-100">Buat voucher</button></form></div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php';
