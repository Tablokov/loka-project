<?php
require_once __DIR__ . '/../includes/fungsi.php';
wajib_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('komplain/index.php');
cek_csrf();
$id = (int)($_POST['id'] ?? 0);
$status = $_POST['status_baru'] ?? '';
$tindakan = trim($_POST['tindakan'] ?? '');
if (!in_array($status, ['Baru','Diproses','Selesai'], true) || $tindakan === '') {
    flash('Isi tindakan dan pilih status.', 'danger'); redirect("komplain/detail.php?id=$id");
}
$pdo->beginTransaction();
$pdo->prepare('UPDATE komplain SET status = ? WHERE id = ?')->execute([$status, $id]);
$pdo->prepare('INSERT INTO riwayat_komplain (komplain_id,tanggal,tindakan,petugas_id,status_baru) VALUES (?,NOW(),?,?,?)')
    ->execute([$id, $tindakan, $_SESSION['user']['id'], $status]);
$pdo->commit();
flash("Status diubah menjadi $status.");
redirect("komplain/detail.php?id=$id");
