USE crm_loka;
-- Klaim hadiah stempel (1 baris = 1 siklus 10 transaksi yang sudah diambil pelanggan). Aman dijalankan berulang.
CREATE TABLE IF NOT EXISTS klaim_stempel (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pelanggan_id INT NOT NULL,
  siklus INT NOT NULL,
  tanggal DATE NOT NULL,
  petugas_id INT NULL,
  UNIQUE KEY uq_pelanggan_siklus (pelanggan_id, siklus),
  FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE
);
-- Catatan aktivitas penting (edit/hapus transaksi, reset PIN, kelola pengguna, klaim stempel, reward diambil)
CREATE TABLE IF NOT EXISTS log_aktivitas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  aksi VARCHAR(60) NOT NULL,
  detail VARCHAR(255) NULL,
  waktu DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
-- Status penukaran reward: 'diklaim' = menunggu diambil di kasir, 'diambil' = sudah diserahkan.
-- (Data lama berstatus 'selesai' dianggap sudah diambil.)
