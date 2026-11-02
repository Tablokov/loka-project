<?php
// Satu halaman login untuk semua pengguna. Jenis akun dikenali otomatis dari data yang cocok:
//   - admin/kasir : username + password
//   - pelanggan   : No. HP + PIN
require_once __DIR__ . '/../includes/member_fungsi.php';
if (!empty($_SESSION['user'])) redirect('dashboard.php');
if (member_saat_ini($pdo)) redirect('member/index.php');
 
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mem_cek_csrf();
    $identitas = trim($_POST['identitas'] ?? ''); $sandi = (string)($_POST['sandi'] ?? '');
    if (mem_terkunci()) {
        $error = 'Terlalu banyak percobaan. Coba lagi dalam beberapa menit.';
    } else {
        // 1) admin / kasir
        $st = $pdo->prepare('SELECT * FROM users WHERE username = ?'); $st->execute([$identitas]); $u = $st->fetch();
        if ($u && password_verify($sandi, $u['password'])) {
            session_regenerate_id(true);
            unset($_SESSION['member_id']); mem_reset_gagal();
            $_SESSION['user'] = ['id'=>$u['id'], 'nama'=>$u['nama'], 'role'=>$u['role']];
            redirect('dashboard.php');
        }
        // 2) pelanggan (No. HP boleh ditulis 08..., +62..., atau dengan spasi/strip)
        $hp = mem_hp($identitas);
        if (preg_match('/^0\d{8,13}$/', $hp)) {
            $st = $pdo->prepare('SELECT p.id, a.pin_hash FROM pelanggan p JOIN akun_pelanggan a ON a.pelanggan_id = p.id WHERE p.no_hp = ?');
            $st->execute([$hp]); $r = $st->fetch();
            if ($r && password_verify($sandi, $r['pin_hash'])) {
                unset($_SESSION['user']);
                mem_login_sebagai($r['id']);
                redirect('member/index.php');
            }
        }
        mem_gagal();
        $error = 'Username / No. HP atau password / PIN salah.';
    }
}
$flash = flash();
?><!doctype html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk | Kopi LOKA</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--o:#ff5a0a;--o-d:#d94600;--ink:#2a1409;--mu:#7a6355;--bg:#fbf7f3;--ln:#e8ddd3}
*{box-sizing:border-box;margin:0}
body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;color:var(--ink);min-height:100vh;display:grid;grid-template-columns:1.05fr 1fr;background:var(--bg)}
.brand{position:relative;overflow:hidden;background:radial-gradient(circle at 30% 20%,#ff7226,var(--o) 55%,#e84c00);color:#fff;display:flex;flex-direction:column;justify-content:center;align-items:center;padding:48px;text-align:center}
.brand .rings{position:absolute;right:-220px;bottom:-220px;width:640px;height:640px;opacity:.16}
.brand .rings2{position:absolute;left:-180px;top:-180px;width:420px;height:420px;opacity:.1}
.logo-card{position:relative;width:min(240px,60%);aspect-ratio:1;border-radius:32px;overflow:hidden;box-shadow:0 20px 50px #7a2a0055,0 0 0 6px #ffffff30}
.logo-card img{width:100%;height:100%;object-fit:cover;display:block}
.brand h2{position:relative;font-size:26px;font-weight:700;margin-top:32px;line-height:1.3}
.brand p{position:relative;opacity:.9;margin-top:8px;max-width:360px;line-height:1.6}
.menu{position:relative;display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-top:28px}
.menu span{background:#fff2;border:1px solid #fff4;backdrop-filter:blur(4px);padding:7px 14px;border-radius:99px;font-size:13px;font-weight:600}
.menu b{color:#ffe3d1;margin-left:6px}
.form-side{display:grid;place-items:center;padding:40px 24px}
.box{width:100%;max-width:400px;animation:in .5s ease both}
@keyframes in{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
.mini{display:none;width:56px;height:56px;border-radius:14px;overflow:hidden;margin-bottom:22px}
.mini img{width:100%;height:100%;object-fit:cover}
h1{font-size:34px;font-weight:800;letter-spacing:-.5px}
.sub{color:var(--mu);margin:6px 0 30px}
label{display:block;font-weight:600;font-size:14px;margin-bottom:7px}
.field{position:relative;margin-bottom:20px}
.field input{width:100%;padding:13px 44px;border:1.5px solid var(--ln);border-radius:12px;background:#fff;font:inherit;color:var(--ink);transition:border-color .15s,box-shadow .15s}
.field input:focus{outline:none;border-color:var(--o);box-shadow:0 0 0 4px #ff5a0a22}
.field svg.i{position:absolute;left:14px;bottom:14px;width:18px;height:18px;stroke:var(--mu);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;pointer-events:none}
.field:focus-within svg.i{stroke:var(--o)}
.eye{position:absolute;right:8px;bottom:6px;width:36px;height:36px;border:0;background:none;border-radius:8px;cursor:pointer;display:grid;place-items:center}
.eye:hover{background:#f3e9e0}
.eye svg{width:18px;height:18px;stroke:var(--mu);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.btn-loka{width:100%;padding:14px;border:0;border-radius:12px;background:linear-gradient(180deg,var(--o),var(--o-d));color:#fff;font:inherit;font-weight:700;font-size:15px;cursor:pointer;box-shadow:0 8px 20px #ff5a0a40;transition:transform .12s,box-shadow .12s}
.btn-loka:hover{transform:translateY(-1px);box-shadow:0 12px 26px #ff5a0a55}
.btn-loka:active{transform:none}
.btn-loka:focus-visible,.eye:focus-visible{outline:3px solid #ff5a0a66;outline-offset:2px}
.err{background:#fff0ec;border:1px solid #ffc7b5;color:#a8310a;padding:11px 14px;border-radius:10px;font-size:14px;margin-bottom:18px}
.ok{background:#eefaf2;border:1px solid #b9e6c9;color:#1c6b3a;padding:11px 14px;border-radius:10px;font-size:14px;margin-bottom:18px}
.alt{margin-top:18px;font-size:14px;color:var(--mu);text-align:center}
.alt a{color:var(--o-d);font-weight:700;text-decoration:none}
.alt a:hover{text-decoration:underline}
.foot{margin-top:28px;font-size:12px;color:var(--mu);text-align:center}
@media(max-width:820px){body{grid-template-columns:1fr}.brand{display:none}.mini{display:block}.form-side{align-items:start;padding-top:10vh}}
@media(prefers-reduced-motion:reduce){.box{animation:none}.btn-loka{transition:none}}
</style>
</head><body>
 
<section class="brand">
  <svg class="rings" viewBox="0 0 200 200" fill="none" stroke="#fff" stroke-width="10" aria-hidden="true"><circle cx="100" cy="100" r="30"/><circle cx="100" cy="100" r="55"/><circle cx="100" cy="100" r="80"/><circle cx="100" cy="100" r="105"/></svg>
  <svg class="rings2" viewBox="0 0 200 200" fill="none" stroke="#fff" stroke-width="10" aria-hidden="true"><circle cx="100" cy="100" r="40"/><circle cx="100" cy="100" r="70"/><circle cx="100" cy="100" r="100"/></svg>
 
  <div class="logo-card"><img src="<?= url('assets/img/logo-loka.png') ?>" alt="Logo Kopi LOKA"></div>
 
  <h2>Kenali pelanggan,<br>jaga setiap gelas tetap istimewa.</h2>
</section>
 
<main class="form-side">
  <div class="box">
    <div class="mini"><img src="<?= url('assets/img/logo-loka.png') ?>" alt="Logo Kopi LOKA"></div>
    <h1>Masuk</h1>
    <p class="sub">Sistem CRM Kopi LOKA Jambi</p>
 
    <?php if ($flash): ?><div class="ok" role="status"><?= e($flash[0]) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="err" role="alert"><?= e($error) ?></div><?php endif; ?>
 
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <div class="field">
        <label for="identitas">Username atau No. HP</label>
        <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
        <input id="identitas" name="identitas" placeholder="Username atau nomor HP" required autofocus autocomplete="username" value="<?= e($_POST['identitas'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="password">Password atau PIN</label>
        <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
        <input id="password" type="password" name="sandi" placeholder="Password atau PIN" required autocomplete="current-password">
        <button type="button" class="eye" id="eye" aria-label="Tampilkan password">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </div>
      <button class="btn-loka" type="submit">Masuk</button>
    </form>
    <p class="alt">Pelanggan baru? <a href="<?= url('member/daftar.php') ?>">Daftar member</a></p>
    <p class="foot">© <?= date('Y') ?> Kopi LOKA Jambi</p>
  </div>
</main>
 
<script>
const p=document.getElementById('password'),e=document.getElementById('eye');
e.addEventListener('click',()=>{const s=p.type==='password';p.type=s?'text':'password';e.setAttribute('aria-label',s?'Sembunyikan password':'Tampilkan password')});
</script>
</body></html>