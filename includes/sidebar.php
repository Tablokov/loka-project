<?php
$menu = [
  ['dashboard.php','bi-speedometer2','Dashboard'],
  ['pelanggan/index.php','bi-people','Pelanggan'],
  ['transaksi/index.php','bi-receipt','Transaksi'],
  ['segmentasi/index.php','bi-diagram-3','Segmentasi'],
  ['komplain/index.php','bi-chat-left-text','Komplain'],
  ['loyalty/poin.php','bi-gift','Loyalty'],
  ['kampanye/index.php','bi-megaphone','Kampanye'],
];
if (($_SESSION['user']['role'] ?? '') === 'admin') $menu[] = ['pengguna/index.php','bi-person-gear','Pengguna'];
$sekarang = $_SERVER['REQUEST_URI'];
?>
<nav class="sidebar" id="sidebar" aria-label="Menu utama">
  <a class="brand" href="<?= url('dashboard.php') ?>">
    <img src="<?= url('assets/img/logo-loka.png') ?>" alt="Logo Kopi LOKA">
    <span>Kopi LOKA<small>CRM Jambi</small></span>
  </a>
  <ul class="nav flex-column">
  <?php foreach ($menu as [$href,$ikon,$label]):
      $folder = dirname($href) === '.' ? $href : dirname($href) . '/';
      $on = strpos($sekarang, $folder) !== false; ?>
    <li class="nav-item">
      <a class="nav-link <?= $on ? 'active' : '' ?>" href="<?= url($href) ?>" <?= $on ? 'aria-current="page"' : '' ?>><i class="bi <?= $ikon ?>" aria-hidden="true"></i><?= $label ?></a>
    </li>
  <?php endforeach; ?>
  </ul>
  <div class="user-box">
    <div class="small fw-bold"><?= e($_SESSION['user']['nama']) ?></div>
    <div class="small opacity-75 mb-2"><?= e($_SESSION['user']['role']) ?></div>
    <a class="btn btn-sm btn-light w-100" href="<?= url('auth/logout.php') ?>"><i class="bi bi-box-arrow-right me-1"></i>Keluar</a>
  </div>
</nav>
