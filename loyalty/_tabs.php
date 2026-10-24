<?php $tab = basename($_SERVER['SCRIPT_NAME']); ?>
<ul class="nav nav-pills mb-3">
<?php foreach (['poin.php'=>'Poin','reward.php'=>'Reward','tukar.php'=>'Tukar poin','voucher.php'=>'Voucher','promo.php'=>'Promo ulang tahun'] as $f => $l): ?>
  <li class="nav-item"><a class="nav-link <?= $tab === $f ? 'active bg-dark' : 'text-dark' ?>" href="<?= $f ?>"><?= $l ?></a></li>
<?php endforeach; ?>
</ul>
