<?php
require_once __DIR__ . '/../includes/member_fungsi.php';
wajib_admin();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$st = $pdo->prepare('SELECT t.*, p.nama, p.kode FROM transaksi t JOIN pelanggan p ON p.id = t.pelanggan_id WHERE t.id = ?'); $st->execute([$id]); $t = $st->fetch();
if (!$t) { flash('Transaksi tidak ditemukan.', 'warning'); redirect('transaksi/index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mem_cek_csrf();
    $tanggal = $_POST['tanggal'] ?? ''; $total = (int)($_POST['total'] ?? 0);
    $dt = DateTime::createFromFormat('Y-m-d', $tanggal);
    $err = '';
    if (!$dt || $dt->format('Y-m-d') !== $tanggal) $err = 'Tanggal tidak valid.';
    elseif ($tanggal > date('Y-m-d')) $err = 'Tanggal transaksi tidak boleh di masa depan.';
    elseif ($total <= 0 || $total > 10000000) $err = 'Total harus lebih dari 0 dan maksimal Rp10.000.000.';
    if (!$err) {
        $pdo->beginTransaction();
        try {
            $saldo = total_poin($pdo, $t['pelanggan_id']);
            $kat = kategori_loyalitas($saldo - $t['poin_didapat']);        // kategori sebelum transaksi ini
            $poinBaru = hitung_poin($total, $kat);
            if ($saldo - $t['poin_didapat'] + $poinBaru < 0) throw new Exception('Perubahan ditolak: sebagian poin dari transaksi ini sudah ditukarkan pelanggan.');
            $pdo->prepare('UPDATE transaksi SET tanggal = ?, total = ?, poin_didapat = ? WHERE id = ?')->execute([$tanggal, $total, $poinBaru, $id]);
            $u = $pdo->prepare('UPDATE poin_pelanggan SET jumlah = ?, tanggal = ? WHERE ' . sql_poin_transaksi());
            $u->execute(array_merge([$poinBaru, $tanggal], par_poin_transaksi($t['pelanggan_id'], $id)));
            if (!$u->rowCount() && $poinBaru > 0 && $poinBaru !== (int)$t['poin_didapat']) {
                $pdo->prepare("INSERT INTO poin_pelanggan (pelanggan_id,jenis,jumlah,keterangan,tanggal) VALUES (?, 'masuk', ?, ?, ?)")->execute([$t['pelanggan_id'], $poinBaru, "Transaksi #$id (koreksi)", $tanggal]);
            }
            catat_log($pdo, 'Edit transaksi', "#$id {$t['kode']}: " . rupiah($t['total']) . ' -> ' . rupiah($total) . ", poin {$t['poin_didapat']} -> $poinBaru");
            $pdo->commit();
            flash("Transaksi #$id diperbarui. Poin pelanggan disesuaikan ({$t['poin_didapat']} menjadi $poinBaru).");
            redirect('transaksi/index.php');
        } catch (Exception $ex) { if ($pdo->inTransaction()) $pdo->rollBack(); $err = $ex->getMessage(); }
    }
    flash($err, 'danger'); redirect('transaksi/edit.php?id=' . $id);
}
$judul = 'Edit transaksi';
require __DIR__ . '/../includes/header.php';
?>
<form method="post" class="kartu" style="max-width:560px">
  <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="id" value="<?= $id ?>">
  <p class="mb-3">Pelanggan: <strong><?= e($t['nama']) ?></strong> <span class="text-muted small"><?= e($t['kode']) ?></span> &middot; Transaksi #<?= $id ?></p>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label" for="tanggal">Tanggal</label><input class="form-control" type="date" id="tanggal" name="tanggal" max="<?= date('Y-m-d') ?>" value="<?= e($t['tanggal']) ?>" required></div>
    <div class="col-md-6"><label class="form-label" for="total">Total (Rp)</label><input class="form-control" type="number" id="total" name="total" min="1" step="500" value="<?= (int)$t['total'] ?>" required></div>
  </div>
  <p class="form-text mt-2">Poin dihitung ulang dari total baru (saat ini <?= (int)$t['poin_didapat'] ?> poin) dan saldo pelanggan ikut disesuaikan. Perubahan dicatat di log aktivitas.</p>
  <div class="mt-3 d-flex gap-2"><button class="btn btn-loka">Simpan perubahan</button><a class="btn btn-outline-secondary" href="index.php">Batal</a></div>
</form>
<?php require __DIR__ . '/../includes/footer.php';
