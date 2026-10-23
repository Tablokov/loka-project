<?php
require_once __DIR__ . '/../includes/fungsi.php';
if (!empty($_SESSION['user'])) redirect('dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $st = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute([trim($_POST['username'] ?? '')]);
    $u = $st->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id'=>$u['id'], 'nama'=>$u['nama'], 'role'=>$u['role']];
        redirect('dashboard.php');
    }
    $error = 'Username atau password salah.';
}
?><!doctype html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk | CRM Kopi LOKA</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head><body>
<div class="login-wrap">
  <div class="login-brand"><img src="<?= url('assets/img/logo-loka.png') ?>" alt="Logo Kopi LOKA"></div>
  <div class="login-form">
    <form method="post" style="width:100%;max-width:340px">
      <h1 class="h3 mb-1">Masuk</h1>
      <p class="text-muted mb-4">Sistem CRM Kopi LOKA Jambi</p>
      <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <label class="form-label" for="username">Username</label>
      <input class="form-control mb-3" id="username" name="username" required autofocus autocomplete="username">
      <label class="form-label" for="password">Password</label>
      <input class="form-control mb-4" id="password" type="password" name="password" required autocomplete="current-password">
      <button class="btn btn-loka w-100">Masuk</button>
    </form>
  </div>
</div>
</body></html>
