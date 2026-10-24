<?php
require_once __DIR__ . '/../includes/member_fungsi.php';
if (member_saat_ini($pdo)) redirect('member/index.php');
$judul = 'Masuk Member';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    if (mem_terkunci()) {
        $error = 'Terlalu banyak percobaan. Coba lagi dalam beberapa menit.';
    } else {
        $hp = mem_hp($_POST['no_hp'] ?? '');
        $st = $pdo->prepare('SELECT p.id, a.pin_hash FROM pelanggan p JOIN akun_pelanggan a ON a.pelanggan_id = p.id WHERE p.no_hp = ?');
        $st->execute([$hp]);
        $r = $st->fetch();
        if ($r && password_verify((string)($_POST['pin'] ?? ''), $r['pin_hash'])) {
            mem_login_sebagai($r['id']);
            redirect('member/index.php');
        }
        mem_gagal();
        $error = 'No. HP atau PIN salah.';
    }
}
require __DIR__ . '/_auth_head.php';
?>
<div class="auth-wrap"><div class="mk auth-kartu">
  <div class="text-center mb-4">
    <img class="logo mb-3" src="<?= url('assets/img/logo-loka.png') ?>" alt="Logo Kopi LOKA">
    <h1 class="h4 fw-bold mb-1">Member Kopi LOKA</h1>
    <p class="text-redup small mb-0">Cek poin, voucher, dan tukar hadiahmu.</p>
  </div>
  <?php if ($f = flash()): ?><div class="alert alert-<?= e($f[1]) ?> py-2"><?= e($f[0]) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <label class="form-label" for="no_hp">Nomor WhatsApp / HP</label>
    <input class="form-control mb-3" id="no_hp" name="no_hp" inputmode="tel" required autofocus autocomplete="username" placeholder="08xxxxxxxxxx" value="<?= e($_POST['no_hp'] ?? '') ?>">
    <label class="form-label" for="pin">PIN (6 digit)</label>
    <input class="form-control mb-4" id="pin" name="pin" type="password" inputmode="numeric" maxlength="6" required autocomplete="current-password">
    <button class="mbtn w-100">Masuk</button>
  </form>
  <p class="text-center small mt-4 mb-1">Belum punya akun? <a href="daftar.php">Daftar member</a></p>
  <p class="text-center small mb-0 text-redup">Staf kedai? <a href="<?= url('auth/login.php') ?>">Masuk sebagai admin/kasir</a></p>
</div></div>
</body></html>
