<?php
require_once __DIR__ . '/../includes/member_fungsi.php';
$m = wajib_member($pdo);
$saldo = total_poin($pdo, $m['id']);
$kat = kategori_loyalitas($saldo);
$aktif = $aktif ?? '';
$judul = $judul ?? 'Member';
$menuMember = [
  'index'   => ['index.php',   'bi-person-vcard', 'Kartu Digital'],
  'keluhan' => ['keluhan.php', 'bi-headset',      'Layanan Keluhan'],
  'tukar'   => ['tukar.php',   'bi-gift',         'Tukar Poin'],
  'riwayat' => ['riwayat.php', 'bi-receipt',      'Riwayat Pesanan'],
  'profil'  => ['profil.php',  'bi-person-gear',  'Profil Saya'],
];
?><!doctype html>
<html lang="id" data-bs-theme="dark">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul) ?> | Kopi LOKA Member</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= url('assets/css/member.css') ?>" rel="stylesheet">
</head>
<body class="member">
<header class="mtop"><div class="mtop-in">
  <a class="mbrand" href="index.php"><img src="<?= url('assets/img/logo-loka.png') ?>" alt="Logo Kopi LOKA">
    <span><b>KOPI LOKA</b><small>JAMBI &middot; MEMBER PORTAL</small></span></a>
  <nav class="mnav" aria-label="Menu member">
  <?php foreach ($menuMember as $k => [$href, $ikon, $label]): ?>
    <a href="<?= $href ?>" class="<?= $aktif === $k ? 'aktif' : '' ?>" <?= $aktif === $k ? 'aria-current="page"' : '' ?>><i class="bi <?= $ikon ?>"></i><?= $label ?></a>
  <?php endforeach; ?>
  </nav>
  <div class="mtools">
    <a class="poin-pill text-decoration-none" href="tukar.php" title="Saldo poin"><i class="bi bi-coin"></i><?= number_format($saldo, 0, ',', '.') ?> <small>Pts</small></a>
    <div class="dropdown">
      <button class="akun-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><span class="avatar"><?= e(mem_inisial($m['nama'])) ?></span><span class="nama"><?= e(strtok($m['nama'], ' ')) ?></span></button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="profil.php"><i class="bi bi-person-gear me-2"></i>Profil saya</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
      </ul>
    </div>
  </div>
</div></header>
<main class="mwrap">
<?php if ($f = flash()): ?>
  <div class="alert alert-<?= e($f[1]) ?> alert-dismissible fade show" role="alert"><?= e($f[0]) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div>
<?php endif; ?>
