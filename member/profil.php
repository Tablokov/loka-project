<?php
require_once __DIR__ . '/../includes/member_fungsi.php';
$m = wajib_member($pdo);
$menuPref = ['Kopi Susu Loka','Kopi Loka Creamy','Salted Caramel','Butterscotch','Triple Peach','Cold Apple','Cold Brew','Cold Stone','Chocolate Loka','Cotton Candy','Creamy Milk','Matcha'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $nama = trim($_POST['nama'] ?? ''); $hp = mem_hp($_POST['no_hp'] ?? ''); $email = trim($_POST['email'] ?? '');
    $lahir = trim($_POST['tgl_lahir'] ?? ''); $alamat = trim($_POST['alamat'] ?? ''); $pref = $_POST['preferensi'] ?? '';
    $pinLama = (string)($_POST['pin_lama'] ?? ''); $pinBaru = (string)($_POST['pin_baru'] ?? '');
    $cek = $pdo->prepare('SELECT COUNT(*) FROM pelanggan WHERE no_hp = ? AND id <> ?'); $cek->execute([$hp, $m['id']]);
    $lahirOk = $lahir === '' || (($d = DateTime::createFromFormat('Y-m-d', $lahir)) && $d->format('Y-m-d') === $lahir && $lahir <= date('Y-m-d'));
    $err = '';
    if ($nama === '') $err = 'Nama wajib diisi.';
    elseif (!preg_match('/^0\d{8,13}$/', $hp)) $err = 'Nomor HP tidak valid.';
    elseif ($cek->fetchColumn()) $err = 'Nomor HP ini sudah dipakai member lain.';
    elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Format email tidak valid.';
    elseif (!$lahirOk) $err = 'Tanggal lahir tidak valid.';
    elseif ($pref !== '' && !in_array($pref, $menuPref, true)) $err = 'Menu favorit tidak valid.';
    elseif ($pinBaru !== '') {
        $a = $pdo->prepare('SELECT pin_hash FROM akun_pelanggan WHERE pelanggan_id = ?'); $a->execute([$m['id']]); $a = $a->fetch();
        if (!ctype_digit($pinBaru) || strlen($pinBaru) !== 6) $err = 'PIN baru harus 6 digit angka.';
        elseif (!$a || !password_verify($pinLama, $a['pin_hash'])) $err = 'PIN lama salah. PIN tidak diubah dan profil belum disimpan.';
    }
    if ($err) flash($err, 'danger');
    else {
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE pelanggan SET nama=?, no_hp=?, email=?, alamat=?, tgl_lahir=?, preferensi=? WHERE id=?')
            ->execute([$nama, $hp, $email ?: null, $alamat ?: null, $lahir ?: null, $pref ?: null, $m['id']]);
        if ($pinBaru !== '') $pdo->prepare('UPDATE akun_pelanggan SET pin_hash = ? WHERE pelanggan_id = ?')->execute([password_hash($pinBaru, PASSWORD_DEFAULT), $m['id']]);
        $pdo->commit();
        flash($pinBaru !== '' ? 'Profil dan PIN berhasil diperbarui.' : 'Profil berhasil diperbarui.');
    }
    redirect('member/profil.php');
}
$judul = 'Profil Saya'; $aktif = 'profil';
require __DIR__ . '/_header.php';
$seg = $pdo->prepare('SELECT segmen FROM segmentasi_pelanggan WHERE pelanggan_id = ?'); $seg->execute([$m['id']]); $seg = $seg->fetchColumn();
$tot = $pdo->prepare('SELECT COALESCE(SUM(total),0) FROM transaksi WHERE pelanggan_id = ?'); $tot->execute([$m['id']]); $tot = $tot->fetchColumn();
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
  <div><h1 class="mjudul"><i class="bi bi-person-gear text-warning me-2"></i>Pengaturan Akun &amp; Profil</h1><p class="msub">Perbarui data kontak, preferensi, dan keamanan akun member Kopi LOKA.</p></div>
  <a class="mbtn gelap" href="index.php"><i class="bi bi-person-vcard"></i>Lihat Kartu Digital</a>
</div>
<div class="row g-3">
  <div class="col-lg-8"><form method="post" class="mk">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <div class="d-flex justify-content-between mb-3"><h2 class="h5 mb-0"><i class="bi bi-person-vcard text-warning me-2"></i>Informasi Data Diri</h2><code class="text-warning"><?= e($m['kode']) ?></code></div>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label" for="nama">Nama lengkap *</label><input class="form-control" id="nama" name="nama" required value="<?= e($m['nama']) ?>"></div>
      <div class="col-md-6"><label class="form-label" for="no_hp">Nomor WhatsApp / HP *</label><input class="form-control" id="no_hp" name="no_hp" inputmode="tel" required value="<?= e($m['no_hp']) ?>"></div>
      <div class="col-md-6"><label class="form-label" for="email">Alamat email</label><input class="form-control" id="email" type="email" name="email" value="<?= e($m['email']) ?>"></div>
      <div class="col-md-6"><label class="form-label" for="tgl_lahir">Tanggal lahir</label><input class="form-control" id="tgl_lahir" type="date" name="tgl_lahir" max="<?= date('Y-m-d') ?>" value="<?= e($m['tgl_lahir']) ?>"></div>
      <div class="col-md-6"><label class="form-label" for="alamat">Alamat / kota domisili</label><input class="form-control" id="alamat" name="alamat" value="<?= e($m['alamat']) ?>"></div>
      <div class="col-md-6"><label class="form-label" for="preferensi">Menu favorit</label><select class="form-select" id="preferensi" name="preferensi"><option value="">Belum diketahui</option>
        <?php foreach ($menuPref as $p): ?><option <?= $m['preferensi'] === $p ? 'selected' : '' ?>><?= e($p) ?></option><?php endforeach; ?></select></div>
    </div>
    <hr class="my-4" style="border-color:var(--garis-lembut)">
    <h3 class="h6"><i class="bi bi-key text-warning me-2"></i>Ubah PIN (kosongkan jika tidak ingin mengubah)</h3>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label" for="pin_lama">PIN lama</label><input class="form-control" id="pin_lama" type="password" inputmode="numeric" maxlength="6" name="pin_lama" autocomplete="current-password"></div>
      <div class="col-md-6"><label class="form-label" for="pin_baru">PIN baru (6 digit)</label><input class="form-control" id="pin_baru" type="password" inputmode="numeric" maxlength="6" name="pin_baru" autocomplete="new-password"></div>
    </div>
    <div class="text-end mt-4"><button class="mbtn"><i class="bi bi-save"></i>Simpan Perubahan</button></div>
  </form></div>
  <div class="col-lg-4">
    <div class="mk mb-3 text-center">
      <span class="avatar besar mb-2"><?= e(mem_inisial($m['nama'])) ?></span>
      <h2 class="h5 mb-0"><?= e($m['nama']) ?></h2><div class="small text-warning mb-2"><?= e($m['kode']) ?></div><span class="mbadge <?= strtolower($kat) ?>"><i class="bi bi-shield-fill me-1"></i><?= $kat ?></span>
      <div class="mstat mt-3 text-start">
        <div class="d-flex justify-content-between small py-1"><span class="text-redup">Tanggal bergabung</span><strong><?= tgl($m['tgl_daftar']) ?></strong></div>
        <div class="d-flex justify-content-between small py-1"><span class="text-redup">Segmentasi</span><strong class="text-warning"><?= e($seg ?: 'Belum dihitung') ?></strong></div>
        <div class="d-flex justify-content-between small py-1"><span class="text-redup">Total belanja</span><strong style="color:var(--hijau)"><?= rupiah($tot) ?></strong></div>
      </div>
    </div>
    <div class="mk"><h3 class="h6"><i class="bi bi-headset text-warning me-2"></i>Pusat Bantuan</h3>
      <p class="small text-redup">Ada kendala dengan akun atau transaksimu? Tim Kopi LOKA siap membantu.</p>
      <a class="mbtn w-100" href="keluhan.php"><i class="bi bi-pencil-square"></i>Buka Tiket Keluhan</a></div>
  </div>
</div>
<?php require __DIR__ . '/_footer.php';
