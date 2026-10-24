USE crm_loka;
-- Data komplain contoh (SIMULASI). Impor sekali saja.
INSERT INTO komplain (pelanggan_id,tanggal,kategori,deskripsi,status,prioritas) VALUES
(1,DATE_SUB(CURDATE(),INTERVAL 12 DAY),'Produk','Kopi Susu Loka terasa terlalu manis dari biasanya.','Selesai','Rendah'),
(3,DATE_SUB(CURDATE(),INTERVAL 6 DAY),'Pelayanan','Pesanan menunggu hampir 25 menit saat ramai.','Diproses','Tinggi'),
(5,DATE_SUB(CURDATE(),INTERVAL 3 DAY),'Kebersihan','Meja di area outdoor masih kotor saat datang.','Baru','Sedang'),
(8,DATE_SUB(CURDATE(),INTERVAL 1 DAY),'Produk','Matcha kurang pekat dan es terlalu banyak.','Baru','Sedang');
SET @k1 := (SELECT id FROM komplain WHERE pelanggan_id=1 ORDER BY id DESC LIMIT 1);
SET @k2 := (SELECT id FROM komplain WHERE pelanggan_id=3 ORDER BY id DESC LIMIT 1);
SET @k3 := (SELECT id FROM komplain WHERE pelanggan_id=5 ORDER BY id DESC LIMIT 1);
SET @k4 := (SELECT id FROM komplain WHERE pelanggan_id=8 ORDER BY id DESC LIMIT 1);
INSERT INTO riwayat_komplain (komplain_id,tanggal,tindakan,petugas_id,status_baru) VALUES
(@k1,DATE_SUB(NOW(),INTERVAL 12 DAY),'Komplain dicatat',2,'Baru'),
(@k1,DATE_SUB(NOW(),INTERVAL 11 DAY),'Barista menyesuaikan takaran gula, minuman diganti gratis',1,'Selesai'),
(@k2,DATE_SUB(NOW(),INTERVAL 6 DAY),'Komplain dicatat',2,'Baru'),
(@k2,DATE_SUB(NOW(),INTERVAL 5 DAY),'Owner menghubungi pelanggan, evaluasi jumlah barista jam ramai',1,'Diproses'),
(@k3,DATE_SUB(NOW(),INTERVAL 3 DAY),'Komplain dicatat',2,'Baru'),
(@k4,DATE_SUB(NOW(),INTERVAL 1 DAY),'Komplain dicatat',2,'Baru');
