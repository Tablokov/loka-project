<?php
ob_start(); // mencegah "headers already sent" saat halaman memproses form lalu melakukan redirect
require_once __DIR__ . '/fungsi.php';
wajib_login();
$judul = $judul ?? 'CRM Kopi LOKA';
$namaUser = $_SESSION['user']['nama'] ?? '';
?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul) ?> | CRM Kopi LOKA</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/sidebar.php'; ?>
<main class="konten">
  <header class="topbar">
    <button class="btn btn-sm d-lg-none" id="toggleMenu" aria-label="Buka menu"><i class="bi bi-list fs-4"></i></button>
    <h1 class="h4"><?= e($judul) ?></h1>
    <div class="user-chip"><span class="av" aria-hidden="true"><?= e(strtoupper(substr($namaUser, 0, 1))) ?></span>
      <span class="nama"><?= e($namaUser) ?><small><?= e($_SESSION['user']['role'] ?? '') ?></small></span></div>
  </header>
  <div class="p-3 p-lg-4">
  <?php if ($f = flash()): ?>
    <div class="alert alert-<?= e($f[1]) ?> alert-dismissible fade show" role="alert">
      <?= e($f[0]) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endif; ?>
