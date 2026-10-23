<?php
$judul = 'Tambah pelanggan';
require __DIR__ . '/../includes/header.php';
$error = ''; $p = $_POST;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    if (trim($p['nama'] ?? '') === '' || trim($p['no_hp'] ?? '') === '') {
        $error = 'Nama dan no. HP wajib diisi.';
    } else {
        $cek = $pdo->prepare('SELECT COUNT(*) FROM pelanggan WHERE no_hp = ?'); $cek->execute([trim($p['no_hp'])]);
        if ($cek->fetchColumn()) {
            $error = 'No. HP ini sudah terdaftar. Cari pelanggannya di daftar pelanggan.';
        } else {
            $no = (int)$pdo->query('SELECT COALESCE(MAX(id),0)+1 FROM pelanggan')->fetchColumn();
            $kode = sprintf('LK-%03d', $no);
            $st = $pdo->prepare('INSERT INTO pelanggan (kode,nama,no_hp,email,alamat,tgl_lahir,tgl_daftar,preferensi) VALUES (?,?,?,?,?,?,?,?)');
            $st->execute([$kode, trim($p['nama']), trim($p['no_hp']), $p['email'] ?: null, $p['alamat'] ?: null,
                          $p['tgl_lahir'] ?: null, $p['tgl_daftar'], $p['preferensi'] ?: null]);
            flash("Pelanggan $kode ditambahkan.");
            redirect('pelanggan/index.php');
        }
    }
}
include '_form.php';
require __DIR__ . '/../includes/footer.php';
