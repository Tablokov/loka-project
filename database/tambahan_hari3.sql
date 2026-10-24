USE crm_loka;
-- PERBAIKAN: kolom segmen terlalu pendek untuk label "Low Value (perlu diaktifkan kembali)".
-- Jalankan sekali di phpMyAdmin (tab SQL). Jika sudah pernah dijalankan, aman diulang.
ALTER TABLE segmentasi_pelanggan MODIFY segmen VARCHAR(60);

-- Voucher & reward contoh (SIMULASI)
INSERT IGNORE INTO voucher (kode,nama,tipe_diskon,nilai,tgl_mulai,tgl_berakhir,pelanggan_id) VALUES
('WELCOME10','Voucher Selamat Datang','persen',10,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 30 DAY),NULL),
('KANGEN5K','Promo Kami Rindu Kamu','nominal',5000,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 30 DAY),NULL),
('GOLD15','Reward Eksklusif Pelanggan Loyal','persen',15,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 30 DAY),NULL);

-- 3 pelanggan tidak aktif (>30 hari) agar kampanye Engagement bisa didemokan (SIMULASI)
INSERT IGNORE INTO pelanggan (kode,nama,no_hp,email,alamat,tgl_lahir,tgl_daftar,preferensi) VALUES
('LK-013','Rafi Hidayat','081211223301','rafi.h@mail.com','Jl. Kapten Pattimura, Jambi Selatan','2001-03-19',DATE_SUB(CURDATE(),INTERVAL 160 DAY),'Cold Brew'),
('LK-014','Salsabila Nur','082122334402','salsa.n@mail.com','Jl. Sultan Agung, Telanaipura','2002-09-11',DATE_SUB(CURDATE(),INTERVAL 150 DAY),'Cotton Candy'),
('LK-015','Yoga Prasetyo','085233445503','yoga.p@mail.com','Jl. Ade Irma Suryani, Jelutung','2000-12-24',DATE_SUB(CURDATE(),INTERVAL 140 DAY),'Salted Caramel');
INSERT INTO transaksi (pelanggan_id,tanggal,total,poin_didapat)
SELECT p.id, DATE_SUB(CURDATE(), INTERVAL x.hari DAY), x.total, FLOOR(x.total/10000)
FROM pelanggan p JOIN (SELECT 'LK-013' k, 52 hari, 24000 total UNION SELECT 'LK-013', 70, 12000 UNION
                       SELECT 'LK-014', 45, 20000 UNION SELECT 'LK-014', 88, 36000 UNION
                       SELECT 'LK-015', 61, 13000) x ON x.k = p.kode
WHERE NOT EXISTS (SELECT 1 FROM transaksi t WHERE t.pelanggan_id = p.id);
INSERT INTO poin_pelanggan (pelanggan_id,jenis,jumlah,keterangan,tanggal)
SELECT t.pelanggan_id,'masuk',t.poin_didapat,CONCAT('Transaksi #',t.id),t.tanggal FROM transaksi t JOIN pelanggan p ON p.id=t.pelanggan_id
WHERE p.kode IN ('LK-013','LK-014','LK-015') AND NOT EXISTS (SELECT 1 FROM poin_pelanggan pp WHERE pp.pelanggan_id = p.id);
