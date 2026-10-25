-- CRM Kopi LOKA (Kota Jambi) - skema + data dummy (SIMULASI)
CREATE DATABASE IF NOT EXISTS crm_loka CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE crm_loka;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS log_aktivitas, klaim_stempel, akun_pelanggan, kampanye_target, kampanye, voucher, penukaran_reward, reward, poin_pelanggan,
  riwayat_komplain, komplain, segmentasi_pelanggan, transaksi, pelanggan, users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','staff') NOT NULL DEFAULT 'staff'
);
CREATE TABLE pelanggan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(20) NOT NULL UNIQUE,
  nama VARCHAR(100) NOT NULL,
  no_hp VARCHAR(20) NOT NULL,
  email VARCHAR(100),
  alamat VARCHAR(255),
  tgl_lahir DATE,
  tgl_daftar DATE NOT NULL,
  preferensi VARCHAR(100)
);
CREATE TABLE akun_pelanggan (
  pelanggan_id INT PRIMARY KEY,
  pin_hash VARCHAR(255) NOT NULL,
  dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE
);
CREATE TABLE klaim_stempel (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pelanggan_id INT NOT NULL,
  siklus INT NOT NULL,
  tanggal DATE NOT NULL,
  petugas_id INT NULL,
  UNIQUE KEY uq_pelanggan_siklus (pelanggan_id, siklus),
  FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE
);
CREATE TABLE log_aktivitas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  aksi VARCHAR(60) NOT NULL,
  detail VARCHAR(255) NULL,
  waktu DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE transaksi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pelanggan_id INT NOT NULL,
  tanggal DATE NOT NULL,
  total INT NOT NULL,
  poin_didapat INT NOT NULL DEFAULT 0,
  FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE
);
CREATE TABLE segmentasi_pelanggan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pelanggan_id INT NOT NULL,
  skor_r TINYINT, skor_f TINYINT, skor_m TINYINT,
  segmen VARCHAR(60),
  kategori_loyalitas ENUM('Bronze','Silver','Gold'),
  customer_value ENUM('High','Medium','Low'),
  tgl_hitung DATE,
  FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE
);
CREATE TABLE komplain (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pelanggan_id INT NOT NULL,
  tanggal DATE NOT NULL,
  kategori ENUM('Produk','Pelayanan','Kebersihan','Lainnya') NOT NULL,
  deskripsi TEXT,
  status ENUM('Baru','Diproses','Selesai') NOT NULL DEFAULT 'Baru',
  prioritas ENUM('Rendah','Sedang','Tinggi') NOT NULL DEFAULT 'Sedang',
  FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE
);
CREATE TABLE riwayat_komplain (
  id INT AUTO_INCREMENT PRIMARY KEY,
  komplain_id INT NOT NULL,
  tanggal DATETIME NOT NULL,
  tindakan TEXT,
  petugas_id INT,
  status_baru ENUM('Baru','Diproses','Selesai'),
  FOREIGN KEY (komplain_id) REFERENCES komplain(id) ON DELETE CASCADE
);
CREATE TABLE poin_pelanggan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pelanggan_id INT NOT NULL,
  jenis ENUM('masuk','keluar') NOT NULL,
  jumlah INT NOT NULL,
  keterangan VARCHAR(150),
  tanggal DATE NOT NULL,
  FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE
);
CREATE TABLE reward (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  poin_dibutuhkan INT NOT NULL,
  stok INT NOT NULL DEFAULT 0,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif'
);
CREATE TABLE penukaran_reward (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pelanggan_id INT NOT NULL,
  reward_id INT NOT NULL,
  tanggal DATE NOT NULL,
  status VARCHAR(20) DEFAULT 'selesai',
  FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE,
  FOREIGN KEY (reward_id) REFERENCES reward(id)
);
CREATE TABLE voucher (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(30) NOT NULL UNIQUE,
  nama VARCHAR(100) NOT NULL,
  tipe_diskon ENUM('persen','nominal') NOT NULL,
  nilai INT NOT NULL,
  tgl_mulai DATE, tgl_berakhir DATE,
  pelanggan_id INT NULL,
  status_pakai ENUM('belum','terpakai') NOT NULL DEFAULT 'belum',
  FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE SET NULL
);
CREATE TABLE kampanye (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  jenis ENUM('Pelanggan Baru','Pelanggan Loyal','Engagement') NOT NULL,
  target_segmen VARCHAR(50),
  tgl_mulai DATE, tgl_selesai DATE,
  voucher_id INT NULL,
  deskripsi TEXT,
  status ENUM('Draft','Aktif','Selesai') NOT NULL DEFAULT 'Draft',
  FOREIGN KEY (voucher_id) REFERENCES voucher(id) ON DELETE SET NULL
);
CREATE TABLE kampanye_target (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kampanye_id INT NOT NULL,
  pelanggan_id INT NOT NULL,
  status_kirim ENUM('belum','terkirim') DEFAULT 'belum',
  status_respon ENUM('belum','merespon','tidak') DEFAULT 'belum',
  FOREIGN KEY (kampanye_id) REFERENCES kampanye(id) ON DELETE CASCADE,
  FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE
);

-- ===== DATA DUMMY (simulasi, bukan data asli Kopi LOKA) =====
-- Login: admin / admin123  |  kasir / kasir123
INSERT INTO users (nama, username, password, role) VALUES
('Owner Kopi LOKA','admin','$2y$10$isTMfYcKEvVToMQxIyMq7eKSSDfZPu9GdVBJyImozU/u9fW/aTBb2','admin'),
('Kasir LOKA','kasir','$2y$10$dRqpQsl9Kf6Yu5aSu06UyuXjB/0uY/8IR23/uRKP4ALn2gmHotXee','staff');

INSERT INTO pelanggan (kode,nama,no_hp,email,alamat,tgl_lahir,tgl_daftar,preferensi) VALUES
('LK-001','Rizky Pratama','081274560011','rizky.p@mail.com','Jl. Sultan Thaha, Telanaipura','2001-11-02',DATE_SUB(CURDATE(),INTERVAL 150 DAY),'Kopi Susu Loka'),
('LK-002','Aulia Putri','082176540022','aulia.putri@mail.com','Jl. Kolonel Abunjani, Sipin','2002-04-18',DATE_SUB(CURDATE(),INTERVAL 140 DAY),'Cotton Candy'),
('LK-003','Dimas Saputra','085266780033','dimas.s@mail.com','Jl. Prof. Dr. Sri Soedewi, Mendalo','2000-12-09',DATE_SUB(CURDATE(),INTERVAL 130 DAY),'Cold Brew'),
('LK-004','Nabila Rahma','081378900044','nabila.r@mail.com','Jl. Orang Kayo Hitam, Telanaipura','2003-07-25',DATE_SUB(CURDATE(),INTERVAL 120 DAY),'Matcha'),
('LK-005','Fajar Ramadhan','082280120055','fajar.r@mail.com','Jl. Slamet Riyadi, Kota Baru','1999-03-14',DATE_SUB(CURDATE(),INTERVAL 110 DAY),'Salted Caramel'),
('LK-006','Siti Nurhaliza','085273450066','siti.n@mail.com','Jl. Jend. Sudirman, The Hok','2001-10-30',DATE_SUB(CURDATE(),INTERVAL 100 DAY),'Creamy Milk'),
('LK-007','Bayu Anggara','081366780077','bayu.a@mail.com','Jl. Lingkar Selatan, Kota Baru','2002-01-21',DATE_SUB(CURDATE(),INTERVAL 90 DAY),'Triple Peach'),
('LK-008','Intan Permata','082177890088','intan.p@mail.com','Jl. Hayam Wuruk, Telanaipura','2000-09-05',DATE_SUB(CURDATE(),INTERVAL 70 DAY),'Butterscotch'),
('LK-009','Galang Mahendra','085377001099','galang.m@mail.com','Jl. Kapten A. Hamid, Jelutung','2003-05-12',DATE_SUB(CURDATE(),INTERVAL 50 DAY),'Cold Stone'),
('LK-010','Citra Dewi','081274112110','citra.d@mail.com','Jl. Jambi - Muara Bulian, Mendalo','2001-02-27',DATE_SUB(CURDATE(),INTERVAL 28 DAY),'Cold Apple'),
('LK-011','Hafiz Alamsyah','082388223121','hafiz.a@mail.com','Jl. Pattimura, Jambi Selatan','2002-08-16',DATE_SUB(CURDATE(),INTERVAL 15 DAY),'Chocolate Loka'),
('LK-012','Putri Maharani','085211334132','putri.m@mail.com','Jl. M. Husni Thamrin, Jambi Timur','2004-06-08',DATE_SUB(CURDATE(),INTERVAL 5 DAY),'Kopi Loka Creamy');

-- Transaksi simulasi: harga mengikuti menu (10K-13K) x jumlah gelas.
-- Pelanggan dengan id kecil cenderung lebih sering dan lebih baru; id besar kurang aktif.
INSERT INTO transaksi (pelanggan_id, tanggal, total, poin_didapat)
SELECT pelanggan_id, tanggal, total, FLOOR(total/10000) FROM (
  SELECT p.id AS pelanggan_id,
    DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND()*(8 + p.id*7)) DAY) AS tanggal,
    ELT(1+FLOOR(RAND()*4),10000,12000,13000,12000) * (1+FLOOR(RAND()*3)) AS total
  FROM pelanggan p
  JOIN (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
        UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12
        UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION SELECT 16) s
    ON s.n <= GREATEST(2, 16 - p.id)
) t;

INSERT INTO poin_pelanggan (pelanggan_id,jenis,jumlah,keterangan,tanggal)
SELECT pelanggan_id,'masuk',poin_didapat,CONCAT('Transaksi #',id),tanggal FROM transaksi WHERE poin_didapat>0;

INSERT INTO reward (nama,poin_dibutuhkan,stok,status) VALUES
('Gratis 1 Kopi Susu Loka',10,50,'aktif'),
('Gratis 1 Matcha',12,40,'aktif'),
('Diskon Loka Literan 10K',30,20,'aktif');
