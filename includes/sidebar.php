<?php
$menu = [
  ['dashboard.php','bi-speedometer2','Dashboard',true],
  ['pelanggan/index.php','bi-people','Pelanggan',true],
  ['transaksi/index.php','bi-receipt','Transaksi',true],
  ['segmentasi/index.php','bi-diagram-3','Segmentasi',true],
  ['komplain/index.php','bi-chat-left-text','Komplain',true],
  ['loyalty/poin.php','bi-gift','Loyalty',false],
  ['kampanye/index.php','bi-megaphone','Kampanye',false],
];
$sekarang = $_SERVER['REQUEST_URI'];
?>
<nav class="sidebar" id="sidebar">
  <a class="brand" href="<?= url('dashboard.php') ?>">
    <img src="<?= url('assets/img/logo-loka.png') ?>" alt="Logo Kopi LOKA">
  </a>
  <ul class="nav flex-column">
  <?php foreach ($menu as [$href,$ikon,$label,$aktif]):
      $folder = dirname($href) === '.' ? $href : dirname($href) . '/';
      $on = strpos($sekarang, $folder) !== false; ?>
    <li class="nav-item">
      <?php if ($aktif): ?>
        <a class="nav-link <?= $on ? 'active' : '' ?>" href="<?= url($href) ?>"><i class="bi <?= $ikon ?>"></i><?= $label ?></a>
      <?php else: ?>
        <span class="nav-link disabled" title="Segera hadir"><i class="bi <?= $ikon ?>"></i><?= $label ?></span>
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
  </ul>
  <div class="user-box">
    <div class="small"><?= e($_SESSION['user']['nama']) ?></div>
    <div class="small opacity-75"><?= e($_SESSION['user']['role']) ?></div>
    <a class="btn btn-sm btn-light mt-2 w-100" href="<?= url('auth/logout.php') ?>">Keluar</a>
  </div>
</nav>
