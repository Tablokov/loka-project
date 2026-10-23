<?php
require_once __DIR__ . '/fungsi.php';
wajib_login();
$judul = $judul ?? 'CRM Kopi LOKA';
?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul) ?> | CRM Kopi LOKA</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/sidebar.php'; ?>
<main class="konten">
  <header class="topbar">
    <button class="btn btn-sm d-lg-none" id="toggleMenu" aria-label="Buka menu"><i class="bi bi-list fs-4"></i></button>
    <h1 class="h4 mb-0"><?= e($judul) ?></h1>
  </header>
  <div class="p-3 p-lg-4">
  <?php if ($f = flash()): ?>
    <div class="alert alert-<?= e($f[1]) ?> alert-dismissible fade show" role="alert">
      <?= e($f[0]) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endif; ?>
