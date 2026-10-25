<?php
require_once __DIR__ . '/../includes/member_fungsi.php';
wajib_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('pelanggan/index.php');
mem_cek_csrf();
$id = (int)($_POST['id'] ?? 0);
$st = $pdo->prepare('SELECT id, nama, kode FROM pelanggan WHERE id = ?'); $st->execute([$id]); $p = $st->fetch();
if (!$p) { flash('Pelanggan tidak ditemukan.', 'warning'); redirect('pelanggan/index.php'); }
$pin = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$ada = $pdo->prepare('SELECT COUNT(*) FROM akun_pelanggan WHERE pelanggan_id = ?'); $ada->execute([$id]); $ada = (bool)$ada->fetchColumn();
$pdo->prepare('INSERT INTO akun_pelanggan (pelanggan_id, pin_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE pin_hash = VALUES(pin_hash)')
    ->execute([$id, password_hash($pin, PASSWORD_DEFAULT)]);
catat_log($pdo, $ada ? 'Reset PIN member' : 'Buat akun member', $p['kode'] . ' ' . $p['nama']);
flash(($ada ? 'PIN direset' : 'Akun member dibuat') . " untuk {$p['nama']}. PIN sementara: $pin. Sampaikan ke pelanggan (PIN ini tidak ditampilkan lagi) dan minta menggantinya di menu Profil.", 'warning');
redirect('pelanggan/detail.php?id=' . $id);
