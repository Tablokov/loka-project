<?php
$judul = 'Loyalty - Tukar poin';
require __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/fungsi_loyalty.php';
require_once __DIR__ . '/../includes/member_fungsi.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mem_cek_csrf();
    if (($_POST['aksi'] ?? '') === 'diambil') {
        $u = $pdo->prepare("UPDATE penukaran_reward SET status = 'diambil' WHERE id = ? AND status = 'diklaim'");
        $u->execute([(int)($_POST['id'] ?? 0)]);
        if ($u->rowCount()) { catat_log($pdo, 'Reward diambil', 'Kode RW-' . str_pad((int)$_POST['id'], 5, '0', STR_PAD_LEFT)); flash('Reward ditandai sudah diambil.'); }
        else flash('Penukaran tidak ditemukan atau sudah ditandai diambil.', 'warning');
        redirect('loyalty/tukar.php');
    }
    $pid = (int)($_POST['pelanggan_id'] ?? 0); $rid = (int)($_POST['reward_id'] ?? 0);
    $pdo->beginTransaction();
    try {
        $r = $pdo->prepare('SELECT * FROM reward WHERE id = ? FOR UPDATE'); $r->execute([$rid]); $r = $r->fetch();
        $pl = $pdo->prepare('SELECT nama FROM pelanggan WHERE id = ?'); $pl->execute([$pid]); $pl = $pl->fetch();
        if (!$r || !$pl) throw new Exception('Pilih pelanggan dan reward.');
        if ($r['status'] !== 'aktif') throw new Exception('Reward ini sedang nonaktif.');
        if ($r['stok'] < 1) throw new Exception('Stok reward habis.');
        $saldo = total_poin($pdo, $pid);
        if ($saldo < $r['poin_dibutuhkan']) throw new Exception("Poin {$pl['nama']} ($saldo) belum cukup, butuh {$r['poin_dibutuhkan']} poin.");
        $pdo->prepare("INSERT INTO penukaran_reward (pelanggan_id,reward_id,tanggal,status) VALUES (?,?,CURDATE(),'diambil')")->execute([$pid,$rid]);
        $pdo->prepare("INSERT INTO poin_pelanggan (pelanggan_id,jenis,jumlah,keterangan,tanggal) VALUES (?, 'keluar', ?, ?, CURDATE())")->execute([$pid,$r['poin_dibutuhkan'],'Tukar reward: ' . $r['nama']]);
        $pdo->prepare('UPDATE reward SET stok = stok - 1 WHERE id = ?')->execute([$rid]);
        $pdo->commit();
        flash("{$pl['nama']} menukar {$r['poin_dibutuhkan']} poin dengan {$r['nama']}. Sisa poin: " . ($saldo - $r['poin_dibutuhkan']) . '.');
    } catch (Exception $ex) { $pdo->rollBack(); flash($ex->getMessage(), 'danger'); }
    redirect('loyalty/tukar.php');
}
$pel = $pdo->query("SELECT p.id, p.kode, p.nama, COALESCE(SUM(CASE pp.jenis WHEN 'masuk' THEN pp.jumlah ELSE -pp.jumlah END),0) saldo
   FROM pelanggan p LEFT JOIN poin_pelanggan pp ON pp.pelanggan_id = p.id GROUP BY p.id ORDER BY p.nama")->fetchAll();
$rew = $pdo->query("SELECT * FROM reward WHERE status='aktif' AND stok > 0 ORDER BY poin_dibutuhkan")->fetchAll();
$kodeCari = trim($_GET['kode'] ?? '');
$sqlRiw = 'SELECT t.id, t.tanggal, t.status, p.nama, r.nama reward, r.poin_dibutuhkan FROM penukaran_reward t JOIN pelanggan p ON p.id=t.pelanggan_id JOIN reward r ON r.id=t.reward_id';
$parRiw = [];
if ($kodeCari !== '' && preg_match('/(\d+)/', $kodeCari, $mk)) { $sqlRiw .= ' WHERE t.id = ?'; $parRiw[] = (int)$mk[1]; }
$sqlRiw .= " ORDER BY (t.status = 'diklaim') DESC, t.id DESC LIMIT 30";
$riw = $pdo->prepare($sqlRiw); $riw->execute($parRiw); $riw = $riw->fetchAll();
include '_tabs.php';
?>
<div class="row g-3">
  <div class="col-lg-5"><form method="post" class="kartu">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <label class="form-label" for="pelanggan_id">Pelanggan</label>
    <select class="form-select mb-3" id="pelanggan_id" name="pelanggan_id" required><option value="">Pilih pelanggan</option>
      <?php foreach ($pel as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['kode'] . ' - ' . $p['nama']) ?> (<?= $p['saldo'] ?> poin)</option><?php endforeach; ?></select>
    <label class="form-label" for="reward_id">Reward</label>
    <select class="form-select mb-3" id="reward_id" name="reward_id" required><option value="">Pilih reward</option>
      <?php foreach ($rew as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['nama']) ?> - <?= $r['poin_dibutuhkan'] ?> poin (stok <?= $r['stok'] ?>)</option><?php endforeach; ?></select>
    <button class="btn btn-loka w-100">Tukar poin</button>
  </form></div>
  <div class="col-lg-7"><div class="kartu p-0">
    <div class="p-3 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2"><h2 class="h6 mb-0">Penukaran terakhir</h2>
      <form class="d-flex gap-2" method="get"><input class="form-control form-control-sm" name="kode" value="<?= e($kodeCari) ?>" placeholder="Cari kode klaim, mis. RW-00001" aria-label="Cari kode klaim"><button class="btn btn-sm btn-outline-loka">Cari</button></form></div>
    <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Kode</th><th>Tanggal</th><th>Pelanggan</th><th>Reward</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($riw as $r): $menunggu = $r['status'] === 'diklaim'; ?>
      <tr><td><code>RW-<?= str_pad($r['id'], 5, '0', STR_PAD_LEFT) ?></code></td><td><?= tgl($r['tanggal']) ?></td><td><?= e($r['nama']) ?></td><td><?= e($r['reward']) ?> <span class="text-muted small">(-<?= $r['poin_dibutuhkan'] ?>)</span></td>
        <td><span class="badge text-bg-<?= $menunggu ? 'warning' : 'success' ?>"><?= $menunggu ? 'Menunggu diambil' : 'Sudah diambil' ?></span></td>
        <td><?php if ($menunggu): ?><form method="post" onsubmit="return confirm('Serahkan reward <?= e(addslashes($r['reward'])) ?> kepada <?= e(addslashes($r['nama'])) ?>?')"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="aksi" value="diambil"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-loka">Tandai diambil</button></form><?php endif; ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$riw): ?><tr><td colspan="6" class="text-center text-muted py-3"><?= $kodeCari !== '' ? 'Kode klaim tidak ditemukan.' : 'Belum ada penukaran.' ?></td></tr><?php endif; ?>
    </tbody></table></div></div></div>
</div>
<?php require __DIR__ . '/../includes/footer.php';
