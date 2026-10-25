<?php
require_once __DIR__ . '/../includes/member_fungsi.php';
wajib_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('pelanggan/index.php');
mem_cek_csrf();
$id = (int)($_POST['id'] ?? 0);
$st = $pdo->prepare('SELECT id, nama, kode FROM pelanggan WHERE id = ?'); $st->execute([$id]); $p = $st->fetch();
if (!$p) { flash('Pelanggan tidak ditemukan.', 'warning'); redirect('pelanggan/index.php'); }
$s = stempel_status($pdo, $id);
if ($s['tersedia'] < 1) { flash('Belum ada hadiah stempel yang bisa diklaim (stempel belum penuh atau sudah diklaim semua).', 'warning'); redirect('pelanggan/detail.php?id=' . $id); }
try {
    $siklus = $s['diklaim'] + 1;
    $pdo->prepare('INSERT INTO klaim_stempel (pelanggan_id, siklus, tanggal, petugas_id) VALUES (?, ?, CURDATE(), ?)')->execute([$id, $siklus, $_SESSION['user']['id']]);
    catat_log($pdo, 'Klaim stempel', "{$p['kode']} {$p['nama']} siklus $siklus");
    flash("Klaim hadiah stempel siklus $siklus dicatat untuk {$p['nama']}. Serahkan: " . STEMPEL_HADIAH . '.');
} catch (PDOException $ex) { flash('Klaim ini sudah tercatat sebelumnya.', 'warning'); }
redirect('pelanggan/detail.php?id=' . $id);
