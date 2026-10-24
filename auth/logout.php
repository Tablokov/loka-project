<?php
require_once __DIR__ . '/../includes/fungsi.php';
$_SESSION = [];
session_destroy();
redirect('auth/login.php?tab=staff');
