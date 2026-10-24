<?php
require_once __DIR__ . '/../includes/member_fungsi.php';
if (member_saat_ini($pdo)) redirect('member/index.php');
$judul = 'Daftar Member';
$error = '';
$in = ['nama'=>'','no_hp'=>'','email'=>'','tgl_lahir'=>'','alamat'=>''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mem_cek_csrf();
    foreach ($in as $k => $_) $in[$k] = trim($_POST[$k] ?? '');
    $hp = mem_hp($in['no_hp']); $pin = (string)($_POST['pin'] ?? ''); $pin2 = (string)($_POST['pin2'] ?? '');
    $lahirOk = $in['tgl_lahir'] !== '' && ($d = DateTime::createFromFormat('Y-m-d', $in['tgl_lahir'])) && $d->format('Y-m-d') === $in['tgl_lahir'] && $in['tgl_lahir'] <= date('Y-m-d');
    if ($in['nama'] === '') $error = 'Nama wajib diisi.';
    elseif (!preg_match('/^0\d{8,13}$/', $hp)) $error = 'Nomor HP tidak valid. Contoh: 081234567890.';
    elseif ($in['email'] !== '' && !filter_var($in['email'], FILTER_VALIDATE_EMAIL)) $error = 'Format email tidak valid.';
    elseif (!$lahirOk) $error = 'Isi tanggal lahir dengan benar.';
    elseif (!ctype_digit($pin) || strlen($pin) !== 6) $error = 'PIN harus 6 digit angka.';
    elseif ($pin !== $pin2) $error = 'Konfirmasi PIN tidak sama.';
    else {
        $st = $pdo->prepare('SELECT p.*, (SELECT COUNT(*) FROM akun_pelanggan a WHERE a.pelanggan_id = p.id) AS punya_akun FROM pelanggan p WHERE p.no_hp = ?');
        $st->execute([$hp]); $ada = $st->fetch();
        $hash = password_hash($pin, PASSWORD_DEFAULT);
        if ($ada) {
            if ($ada['punya_akun']) $error = 'Nomor ini sudah punya akun. Silakan masuk.';
            elseif (!$ada['tgl_lahir'] || $ada['tgl_lahir'] !== $in['tgl_lahir']) $error = 'Nomor ini sudah tercatat di kasir. Untuk mengaktifkan akun, isi tanggal lahir yang sama dengan data di kasir, atau tanyakan ke kasir.';
            else {
                $pdo->prepare('INSERT INTO akun_pelanggan (pelanggan_id, pin_hash) VALUES (?, ?)')->execute([$ada['id'], $hash]);
                mem_login_sebagai($ada['id']); flash('Akun member berhasil diaktifkan. Selamat datang kembali!');
                redirect('member/index.php');
            }
        } else {
            $pdo->beginTransaction();
            try {
                $no = (int)$pdo->query('SELECT COALESCE(MAX(id),0)+1 FROM pelanggan')->fetchColumn();
                $pdo->prepare('INSERT INTO pelanggan (kode,nama,no_hp,email,alamat,tgl_lahir,tgl_daftar) VALUES (?,?,?,?,?,?,CURDATE())')
                    ->execute([sprintf('LK-%03d', $no), $in['nama'], $hp, $in['email'] ?: null, $in['alamat'] ?: null, $in['tgl_lahir']]);
                $id = (int)$pdo->lastInsertId();
                $pdo->prepare('INSERT INTO akun_pelanggan (pelanggan_id, pin_hash) VALUES (?, ?)')->execute([$id, $hash]);
                $pdo->commit();
                mem_login_sebagai($id); flash('Pendaftaran berhasil. Selamat datang di Kopi LOKA!');
                redirect('member/index.php');
            } catch (Exception $ex) { $pdo->rollBack(); $error = 'Pendaftaran gagal. Coba lagi.'; }
        }
    }
}
require __DIR__ . '/_auth_head.php';
?>
<div class="auth-wrap"><div class="mk auth-kartu" style="max-width:520px">
  <div class="text-center mb-4">
    <img class="logo mb-3" src="<?= url('assets/img/logo-loka.png') ?>" alt="Logo Kopi LOKA">
    <h1 class="h4 fw-bold mb-1">Daftar Member</h1>
    <p class="text-redup small mb-0">Sudah pernah beli dan tercatat di kasir? Daftar dengan nomor HP yang sama untuk mengaktifkan akunmu.</p>
  </div>
  <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
  <form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>">
    <div class="row g-3">
      <div class="col-12"><label class="form-label" for="nama">Nama lengkap</label><input class="form-control" id="nama" name="nama" required value="<?= e($in['nama']) ?>"></div>
      <div class="col-md-6"><label class="form-label" for="no_hp">Nomor WhatsApp / HP</label><input class="form-control" id="no_hp" name="no_hp" inputmode="tel" required value="<?= e($in['no_hp']) ?>"></div>
      <div class="col-md-6"><label class="form-label" for="tgl_lahir">Tanggal lahir</label><input class="form-control" id="tgl_lahir" type="date" name="tgl_lahir" max="<?= date('Y-m-d') ?>" required value="<?= e($in['tgl_lahir']) ?>"></div>
      <div class="col-12"><label class="form-label" for="email">Email (opsional)</label><input class="form-control" id="email" type="email" name="email" value="<?= e($in['email']) ?>"></div>
      <div class="col-12"><label class="form-label" for="alamat">Alamat / kota (opsional)</label><input class="form-control" id="alamat" name="alamat" value="<?= e($in['alamat']) ?>"></div>
      <div class="col-md-6"><label class="form-label" for="pin">PIN 6 digit</label><input class="form-control" id="pin" type="password" inputmode="numeric" maxlength="6" name="pin" required autocomplete="new-password"></div>
      <div class="col-md-6"><label class="form-label" for="pin2">Ulangi PIN</label><input class="form-control" id="pin2" type="password" inputmode="numeric" maxlength="6" name="pin2" required autocomplete="new-password"></div>
    </div>
    <button class="mbtn w-100 mt-4">Daftar &amp; masuk</button>
  </form>
  <p class="text-center small mt-4 mb-0">Sudah punya akun? <a href="<?= url('auth/login.php?tab=pelanggan') ?>">Masuk</a></p>
</div></div>
</body></html>
