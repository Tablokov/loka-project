<?php
$judul = 'Edit pelanggan';
require __DIR__ . '/../includes/header.php';
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM pelanggan WHERE id = ?'); $st->execute([$id]);
$p = $st->fetch();
if (!$p) { flash('Pelanggan tidak ditemukan.', 'warning'); redirect('pelanggan/index.php'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $in = $_POST;
    $cek = $pdo->prepare('SELECT COUNT(*) FROM pelanggan WHERE no_hp = ? AND id <> ?'); $cek->execute([trim($in['no_hp'] ?? ''), $id]);
    if (trim($in['nama'] ?? '') === '' || trim($in['no_hp'] ?? '') === '') {
        $error = 'Nama dan no. HP wajib diisi.'; $p = array_merge($p, $in);
    } elseif ($cek->fetchColumn()) {
        $error = 'No. HP ini sudah dipakai pelanggan lain.'; $p = array_merge($p, $in);
    } else {
        $up = $pdo->prepare('UPDATE pelanggan SET nama=?,no_hp=?,email=?,alamat=?,tgl_lahir=?,tgl_daftar=?,preferensi=? WHERE id=?');
        $up->execute([trim($in['nama']), trim($in['no_hp']), $in['email'] ?: null, $in['alamat'] ?: null,
                      $in['tgl_lahir'] ?: null, $in['tgl_daftar'], $in['preferensi'] ?: null, $id]);
        flash('Data pelanggan diperbarui.');
        redirect('pelanggan/detail.php?id=' . $id);
    }
}
include '_form.php';
require __DIR__ . '/../includes/footer.php';
