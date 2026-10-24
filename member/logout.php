<?php
require_once __DIR__ . '/../includes/member_fungsi.php';
unset($_SESSION['member_id']);
session_regenerate_id(true);
flash('Kamu sudah keluar.', 'info');
redirect('auth/login.php?tab=pelanggan');
