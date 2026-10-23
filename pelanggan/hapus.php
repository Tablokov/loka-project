<?php
require_once __DIR__ . '/../includes/fungsi.php';
wajib_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $pdo->prepare('DELETE FROM pelanggan WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
    flash('Pelanggan dihapus.');
}
redirect('pelanggan/index.php');
