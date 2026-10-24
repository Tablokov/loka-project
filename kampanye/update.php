<?php
require_once __DIR__ . '/../includes/fungsi.php';
wajib_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('kampanye/index.php');
cek_csrf();
$kid = (int)($_POST['kampanye_id'] ?? 0);
$aksi = $_POST['aksi'] ?? '';
if ($aksi === 'status') {
    $s = $_POST['status'] ?? '';
    if (in_array($s, ['Draft','Aktif','Selesai'], true)) {
        $pdo->prepare('UPDATE kampanye SET status = ? WHERE id = ?')->execute([$s, $kid]);
        flash("Status kampanye: $s.");
    }
} elseif ($aksi === 'kirim') {
    $pdo->prepare("UPDATE kampanye_target SET status_kirim='terkirim' WHERE id = ? AND kampanye_id = ?")->execute([(int)$_POST['target_id'], $kid]);
    flash('Ditandai terkirim.');
} elseif ($aksi === 'respon') {
    $r = $_POST['respon'] ?? '';
    if (in_array($r, ['merespon','tidak'], true)) {
        $pdo->prepare("UPDATE kampanye_target SET status_respon = ?, status_kirim = 'terkirim' WHERE id = ? AND kampanye_id = ?")->execute([$r, (int)$_POST['target_id'], $kid]);
        flash('Respon dicatat.');
    }
}
redirect("kampanye/detail.php?id=$kid");
