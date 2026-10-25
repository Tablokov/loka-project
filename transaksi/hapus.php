<?php
require_once __DIR__ . '/../includes/member_fungsi.php';
wajib_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('transaksi/index.php');
mem_cek_csrf();
$id = (int)($_POST['id'] ?? 0);
$st = $pdo->prepare('SELECT t.*, p.nama, p.kode FROM transaksi t JOIN pelanggan p ON p.id = t.pelanggan_id WHERE t.id = ?'); $st->execute([$id]); $t = $st->fetch();
if (!$t) { flash('Transaksi tidak ditemukan.', 'warning'); redirect('transaksi/index.php'); }
$pdo->beginTransaction();
try {
    if (total_poin($pdo, $t['pelanggan_id']) - $t['poin_didapat'] < 0) throw new Exception('Transaksi tidak bisa dihapus: poinnya sudah sebagian ditukarkan pelanggan.');
    $pdo->prepare('DELETE FROM poin_pelanggan WHERE ' . sql_poin_transaksi())->execute(par_poin_transaksi($t['pelanggan_id'], $id));
    $pdo->prepare('DELETE FROM transaksi WHERE id = ?')->execute([$id]);
    catat_log($pdo, 'Hapus transaksi', "#$id {$t['kode']} " . tgl($t['tanggal']) . ' ' . rupiah($t['total']) . ", poin {$t['poin_didapat']} dibatalkan");
    $pdo->commit();
    flash("Transaksi #$id dihapus dan {$t['poin_didapat']} poin dibatalkan.");
} catch (Exception $ex) { if ($pdo->inTransaction()) $pdo->rollBack(); flash($ex->getMessage(), 'danger'); }
redirect('transaksi/index.php');
