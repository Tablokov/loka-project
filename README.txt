# ☕ CRM Kopi Loka

Sistem **Customer Relationship Management (CRM)** berbasis web untuk membantu pengelolaan data pelanggan dan mendukung aktivitas hubungan pelanggan pada Kopi Loka.

Proyek ini dikembangkan menggunakan PHP dan MySQL sebagai aplikasi pembelajaran pengembangan sistem informasi berbasis web.

## ✨ Fitur Utama

- **Dashboard** — menampilkan ringkasan informasi sistem.
- **Manajemen Pelanggan** — pengelolaan data pelanggan.
- **Manajemen Member** — pengelolaan data keanggotaan pelanggan.
- **Segmentasi Pelanggan** — pengelompokan pelanggan berdasarkan data yang tersedia.
- **Manajemen Transaksi** — pengelolaan data transaksi.
- **Manajemen Komplain** — pencatatan dan pengelolaan keluhan pelanggan.
- **Program Loyalitas** — pengelolaan program loyalitas pelanggan.
- **Kampanye CRM** — pengelolaan kegiatan kampanye pemasaran.
- **Manajemen Pengguna** — pengelolaan akun pengguna sistem.

## 🛠️ Teknologi

- PHP
- MySQL
- HTML
- CSS
- JavaScript
- Bootstrap
- Laragon atau XAMPP
- phpMyAdmin

*Catatan: sesuaikan daftar teknologi dengan library dan versi yang benar-benar digunakan dalam proyek.*

## 📁 Struktur Proyek

```text
loka-project/
├── assets/
├── auth/
├── config/
├── database/
├── includes/
├── kampanye/
├── komplain/
├── loyalty/
├── member/
├── pelanggan/
├── pengguna/
├── segmentasi/
├── transaksi/
├── dashboard.php
├── index.php
└── README.md
```

## ⚙️ Instalasi dan Menjalankan Aplikasi

### 1. Persiapan

Pastikan perangkat sudah memiliki:
- PHP
- MySQL
- Laragon atau XAMPP
- Browser web

### 2. Unduh Proyek

Clone repository berikut:

```bash
git clone https://github.com/Tablokov/loka-project.git
```

Masuk ke folder proyek:

```bash
cd loka-project
```

### 3. Jalankan Server Lokal

Jika menggunakan Laragon, letakkan folder proyek di direktori `www`.

Jika menggunakan XAMPP, letakkan folder proyek di direktori `htdocs`.

Jalankan Apache dan MySQL melalui aplikasi server lokal yang digunakan.

### 4. Siapkan Database

1. Buka phpMyAdmin melalui browser.
2. Buat database sesuai konfigurasi aplikasi.
3. Impor file database SQL yang tersedia di repository.
4. Buka file `config/koneksi.php`.
5. Sesuaikan host, nama database, username, dan password MySQL.

**Catatan:** pastikan nama file SQL dan nama database mengikuti file yang benar-benar tersedia di repository.

### 5. Akses Aplikasi

Buka browser dan kunjungi alamat lokal sesuai lokasi folder proyek.

Contoh jika folder berada di `htdocs/loka-project`:

```text
http://localhost/loka-project/
```

## 🔐 Akun Demo

Repository sebelumnya mencantumkan akun demo berikut:

| Peran | Username | Password |
|---|---|---|
| Admin | `admin` | `admin123` |
| Kasir | `kasir` | `kasir123` |

Gunakan akun tersebut hanya untuk lingkungan pengembangan lokal jika masih aktif. Ganti kredensial bawaan sebelum aplikasi digunakan pada lingkungan nyata.

## ⚠️ Catatan

- Proyek ini ditujukan untuk pembelajaran dan pengembangan aplikasi CRM.
- Data transaksi yang digunakan pada versi demo merupakan data simulasi.
- Kelengkapan fitur bergantung pada implementasi aplikasi saat ini.
- Jangan menyimpan password atau kredensial sensitif di repository publik.

## 🎯 Tujuan Proyek

Proyek ini bertujuan untuk mempelajari penerapan sistem informasi berbasis web dalam pengelolaan data pelanggan, transaksi, segmentasi, layanan pelanggan, dan program loyalitas.

## 👨‍💻 Repository

GitHub: [Tablokov/loka-project](https://github.com/Tablokov/loka-project)
