<?php
require_once __DIR__ . '/../includes/member_fungsi.php';
wajib_admin();
$saya = (int)$_SESSION['user']['id'];
$jumlahAdmin = fn() => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mem_cek_csrf();
    $aksi = $_POST['aksi'] ?? ''; $uid = (int)($_POST['id'] ?? 0);
    $t = $pdo->prepare('SELECT * FROM users WHERE id = ?'); $t->execute([$uid]); $target = $t->fetch();
    if ($aksi === 'tambah') {
        $nama = trim($_POST['nama'] ?? ''); $user = trim($_POST['username'] ?? ''); $pass = (string)($_POST['password'] ?? ''); $role = $_POST['role'] ?? '';
        $ada = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?'); $ada->execute([$user]);
        if ($nama === '') flash('Nama wajib diisi.', 'danger');
        elseif (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $user)) flash('Username 3-30 karakter: huruf, angka, titik, atau garis bawah.', 'danger');
        elseif (strlen($pass) < 8) flash('Password minimal 8 karakter.', 'danger');
        elseif (!in_array($role, ['admin', 'staff'], true)) flash('Pilih peran akun.', 'danger');
        elseif ($ada->fetchColumn()) flash('Username sudah dipakai.', 'danger');
        else {
            $pdo->prepare('INSERT INTO users (nama, username, password, role) VALUES (?,?,?,?)')->execute([$nama, $user, password_hash($pass, PASSWORD_DEFAULT), $role]);
            catat_log($pdo, 'Tambah pengguna', "$user ($role)"); flash("Akun $user dibuat.");
        }
    } elseif (!$target) {
        flash('Pengguna tidak ditemukan.', 'warning');
    } elseif ($aksi === 'password') {
        $pass = (string)($_POST['password'] ?? '');
        if (strlen($pass) < 8) flash('Password baru minimal 8 karakter.', 'danger');
        else { $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($pass, PASSWORD_DEFAULT), $uid]); catat_log($pdo, 'Ganti password pengguna', $target['username']); flash('Password ' . $target['username'] . ' diganti.'); }
    } elseif ($aksi === 'role') {
        $role = $_POST['role'] ?? '';
        if (!in_array($role, ['admin', 'staff'], true)) flash('Peran tidak valid.', 'danger');
        elseif ($target['role'] === 'admin' && $role === 'staff' && $jumlahAdmin() <= 1) flash('Tidak bisa menurunkan admin terakhir.', 'danger');
        else { $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $uid]); catat_log($pdo, 'Ubah peran pengguna', $target['username'] . ' menjadi ' . $role); flash('Peran ' . $target['username'] . " menjadi $role."); }
    } elseif ($aksi === 'hapus') {
        if ($uid === $saya) flash('Kamu tidak bisa menghapus akunmu sendiri.', 'danger');
        elseif ($target['role'] === 'admin' && $jumlahAdmin() <= 1) flash('Tidak bisa menghapus admin terakhir.', 'danger');
        else { $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]); catat_log($pdo, 'Hapus pengguna', $target['username']); flash('Akun ' . $target['username'] . ' dihapus.'); }
    }
    redirect('pengguna/index.php');
}
$judul = 'Pengguna';
require __DIR__ . '/../includes/header.php';
$users = $pdo->query('SELECT * FROM users ORDER BY role, nama')->fetchAll();
$log = $pdo->query('SELECT l.*, u.nama FROM log_aktivitas l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 25')->fetchAll();
?>
<div class="row g-3">
  <div class="col-lg-8"><div class="kartu p-0"><div class="p-3 pb-2"><h2 class="h6 mb-0">Akun admin &amp; kasir</h2></div>
    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Nama</th><th>Username</th><th>Peran</th><th></th></tr></thead><tbody>
    <?php foreach ($users as $u): $diri = (int)$u['id'] === $saya; ?>
      <tr><td><?= e($u['nama']) ?><?= $diri ? ' <span class="badge text-bg-secondary">Kamu</span>' : '' ?></td><td><code><?= e($u['username']) ?></code></td>
        <td><form method="post" class="d-flex gap-1"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="aksi" value="role"><input type="hidden" name="id" value="<?= $u['id'] ?>">
          <select class="form-select form-select-sm" name="role" style="width:105px" aria-label="Peran <?= e($u['username']) ?>"><option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>admin</option><option value="staff" <?= $u['role'] === 'staff' ? 'selected' : '' ?>>staff</option></select>
          <button class="btn btn-sm btn-outline-secondary">Ubah</button></form></td>
        <td class="text-nowrap">
          <form method="post" class="d-inline-flex gap-1"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="aksi" value="password"><input type="hidden" name="id" value="<?= $u['id'] ?>">
            <input class="form-control form-control-sm" style="width:150px" type="password" name="password" placeholder="Password baru" minlength="8" autocomplete="new-password" aria-label="Password baru <?= e($u['username']) ?>" required>
            <button class="btn btn-sm btn-outline-loka">Ganti</button></form>
          <?php if (!$diri): ?><form method="post" class="d-inline" onsubmit="return confirm('Hapus akun <?= e(addslashes($u['username'])) ?>?')"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $u['id'] ?>">
            <button class="btn btn-sm btn-outline-danger" aria-label="Hapus <?= e($u['username']) ?>"><i class="bi bi-trash"></i></button></form><?php endif; ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div></div></div>
  <div class="col-lg-4"><form method="post" class="kartu">
    <h2 class="h6">Tambah akun</h2><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="aksi" value="tambah">
    <label class="form-label" for="nama">Nama</label><input class="form-control mb-2" id="nama" name="nama" required>
    <label class="form-label" for="username">Username</label><input class="form-control mb-2" id="username" name="username" required autocomplete="off">
    <label class="form-label" for="password">Password (min. 8 karakter)</label><input class="form-control mb-2" id="password" type="password" name="password" minlength="8" required autocomplete="new-password">
    <label class="form-label" for="role">Peran</label><select class="form-select mb-3" id="role" name="role"><option value="staff">staff (kasir)</option><option value="admin">admin (pemilik)</option></select>
    <button class="btn btn-loka w-100">Buat akun</button>
    <p class="form-text mb-0 mt-2">Kasir tidak bisa mengedit/menghapus transaksi, menghitung RFM, atau membuka halaman ini.</p></form></div>
  <div class="col-12"><div class="kartu p-0"><div class="p-3 pb-2"><h2 class="h6 mb-0">Catatan aktivitas terakhir</h2></div>
    <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Waktu</th><th>Petugas</th><th>Aktivitas</th><th>Detail</th></tr></thead><tbody>
    <?php foreach ($log as $l): ?><tr><td class="text-nowrap"><?= date('d/m/Y H:i', strtotime($l['waktu'])) ?></td><td><?= e($l['nama'] ?: '-') ?></td><td><?= e($l['aksi']) ?></td><td class="small text-muted"><?= e($l['detail']) ?></td></tr><?php endforeach; ?>
    <?php if (!$log): ?><tr><td colspan="4" class="text-center text-muted py-3">Belum ada aktivitas tercatat.</td></tr><?php endif; ?>
    </tbody></table></div></div></div>
</div>
<?php require __DIR__ . '/../includes/footer.php';
