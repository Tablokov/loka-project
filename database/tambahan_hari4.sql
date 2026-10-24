USE crm_loka;
-- Akun login pelanggan (No. HP + PIN 6 digit). Aman dijalankan berulang.
CREATE TABLE IF NOT EXISTS akun_pelanggan (
  pelanggan_id INT PRIMARY KEY,
  pin_hash VARCHAR(255) NOT NULL,
  dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE
);
-- Akun demo: pelanggan LK-001 (Rizky Pratama), HP 081274560011, PIN 123456
INSERT IGNORE INTO akun_pelanggan (pelanggan_id, pin_hash)
SELECT id, '$2y$10$qHQStNTtW48pnW68cvQ4d.axcRhVwZ/2Y08mtqd8aAsctDhLG..iG' FROM pelanggan WHERE kode = 'LK-001';
