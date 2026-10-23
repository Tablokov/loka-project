<?php require_once __DIR__ . '/includes/fungsi.php'; redirect(empty($_SESSION['user']) ? 'auth/login.php' : 'dashboard.php');
