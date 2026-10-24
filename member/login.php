<?php
// Halaman login digabung ke auth/login.php. File ini dipertahankan agar tautan lama tetap bekerja.
require_once __DIR__ . '/../includes/fungsi.php';
redirect('auth/login.php?tab=pelanggan');
