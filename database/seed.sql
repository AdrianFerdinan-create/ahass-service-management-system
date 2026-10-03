-- ============================================================
-- SISTEM BOOKING SERVIS AHASS — SEED DATA DEMO CERDAS
-- ============================================================
-- Tujuan: penguji melihat SEMUA skenario dalam <1 menit.
-- Tanggal dihitung RELATIF terhadap CURDATE() sehingga selalu
-- relevan kapan pun seed ini dijalankan.
--
-- Skenario yang disiapkan:
--   ⭐ Besok 10.00   → 2/3 terisi  (penguji booking 1 lagi → error 409)
--   ⭐ Hari ini 13.00 → 3/3 PENUH  (dropdown disabled + label PENUH)
--     Hari ini 09.00 → 1/3, dikonfirmasi (progress bar normal)
--     Hari ini 11.00 → 1 booking dibatalkan (bukti kuota kembali)
--     Varian status : pending, dikonfirmasi, selesai, dibatalkan
--     ~16 booking total, plat & nama Indonesia realistis
--     Data H-1 s/d H-3 untuk grafik 7 hari + riwayat by plat
-- ============================================================

-- ------------------------------------------------------------
-- ⚠️ ENCODING (wajib dijalankan lebih dulu)
--    Set ini mencegah karakter UTF-8 (tanda "—", "°", dsb.)
--    berubah jadi mojibake saat seed di-import lewat CLI Windows
--    (default cp850/ANSI). Lewat phpMyAdmin biasanya sudah aman.
--
--    Untuk CLI mysql, cara paling aman:
--      mysql --default-character-set=utf8mb4 -u root < database/seed.sql
-- ------------------------------------------------------------
SET NAMES utf8mb4;

USE ahass_booking;

-- ------------------------------------------------------------
-- ⚠️ Kosongkan data lama (idempotent — boleh diulang)
-- ------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE riwayat_surat;
TRUNCATE TABLE bookings;
TRUNCATE TABLE paket_servis;
TRUNCATE TABLE template_surat;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- 📦 1. PAKET SERVIS (FR-06)
-- ------------------------------------------------------------
INSERT INTO paket_servis (nama, deskripsi, harga, aktif) VALUES
  ('Servis Ringan',  'Ganti oli, cek rem, cek rantai',        85000.00,  1),
  ('Servis Lengkap', 'Servis ringan + ganti busi + cek CVT',  175000.00, 1),
  ('Ganti Oli',      'Ganti oli mesin + filter',              60000.00,  1),
  ('Paket Part',     'Pilih part sendiri: Klaim, Kampas Rem, V-Belt, dll', 0.00, 1);

-- ------------------------------------------------------------
-- 🏍️ 2. BOOKINGS DEMO (~16 data)
-- ------------------------------------------------------------
INSERT INTO bookings
  (kode_booking, plat_nomor, nama_pelanggan, no_hp, tipe_motor,
   tanggal, jam, paket_id, catatan, status, created_at)
VALUES
  -- ===== HARI INI (CURDATE) =====
  -- 09.00 → 1/3, status dikonfirmasi (tampilan progress bar normal)
  (CONCAT('AHASS-', DATE_FORMAT(CURDATE(),'%y%m%d'), '-A17C'),
   'B 1234 ABC', 'Dwi Prasetyo', '081234567890', 'Vario 160',
   CURDATE(), '09:00:00', 2, 'Rem bunyi blong tipis', 'dikonfirmasi',
   DATE_SUB(NOW(), INTERVAL 30 HOUR)),

  -- 11.00 → booking DIBATALKAN → bukti kuota kembali tersedia
  (CONCAT('AHASS-', DATE_FORMAT(CURDATE(),'%y%m%d'), '-B39K'),
   'B 7788 NNO', 'Maya Sari', '085711223344', 'CB150R',
   CURDATE(), '11:00:00', 1, NULL, 'dibatalkan',
   DATE_SUB(NOW(), INTERVAL 26 HOUR)),

  -- ⭐ 13.00 → 3/3 PENUH (mendemonstrasikan slot penuh)
  (CONCAT('AHASS-', DATE_FORMAT(CURDATE(),'%y%m%d'), '-C52M'),
   'D 4567 XYZ', 'Siti Aminah', '081399887766', 'Beat',
   CURDATE(), '13:00:00', 1, NULL, 'pending',
   DATE_SUB(NOW(), INTERVAL 20 HOUR)),

  (CONCAT('AHASS-', DATE_FORMAT(CURDATE(),'%y%m%d'), '-D84Q'),
   'F 8912 KLM', 'Rudi Hartono', '082155667788', 'PCX',
   CURDATE(), '13:00:00', 2, 'CVT getar saat akselerasi', 'dikonfirmasi',
   DATE_SUB(NOW(), INTERVAL 18 HOUR)),

  (CONCAT('AHASS-', DATE_FORMAT(CURDATE(),'%y%m%d'), '-E61R'),
   'A 3344 JKT', 'Andi Wijaya', '081377889900', 'Supra X',
   CURDATE(), '13:00:00', 3, NULL, 'selesai',
   DATE_SUB(NOW(), INTERVAL 15 HOUR)),

  -- ===== BESOK (H+1) =====
  -- ⭐ 10.00 → 2/3 terisi — penguji booking ke-3 → 409 (karena kuota 3, ini sudah 2, ke-3 jadi 3/3, ke-4 error)
  (CONCAT('AHASS-', DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY),'%y%m%d'), '-F27T'),
   'L 1122 BKS', 'Joko Susilo', '081222334455', 'Vario 125',
   DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', 1, NULL, 'dikonfirmasi',
   DATE_SUB(NOW(), INTERVAL 12 HOUR)),

  (CONCAT('AHASS-', DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY),'%y%m%d'), '-G93V'),
   'B 5566 TTR', 'Fitri Handayani', '085633445566', 'Beat',
   DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', 3, 'Bawa oli sendiri', 'pending',
   DATE_SUB(NOW(), INTERVAL 10 HOUR)),

  (CONCAT('AHASS-', DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY),'%y%m%d'), '-H48W'),
   'M 9911 SBY', 'Bambang Setiawan', '081344556677', 'PCX',
   DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', 2, NULL, 'pending',
   DATE_SUB(NOW(), INTERVAL 9 HOUR)),

  (CONCAT('AHASS-', DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY),'%y%m%d'), '-J15X'),
   'B 3355 DPN', 'Nadia Putri', '081288990011', 'Scoopy',
   DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00', 1, 'Kampas rem dekak', 'pending',
   DATE_SUB(NOW(), INTERVAL 8 HOUR)),

  -- ===== H+2 =====
  (CONCAT('AHASS-', DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY),'%y%m%d'), '-K62Y'),
   'B 2244 WKS', 'Hendra Gunawan', '082177665544', 'Supra X',
   DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00:00', 4, 'Ganti V-Belt + roller', 'pending',
   DATE_SUB(NOW(), INTERVAL 7 HOUR)),

  (CONCAT('AHASS-', DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY),'%y%m%d'), '-L79Z'),
   'D 6677 KTA', 'Lestari Dewi', '085611002233', 'Vario 160',
   DATE_ADD(CURDATE(), INTERVAL 2 DAY), '15:00:00', 2, NULL, 'pending',
   DATE_SUB(NOW(), INTERVAL 6 HOUR)),

  -- ===== H+3 =====
  (CONCAT('AHASS-', DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY),'%y%m%d'), '-M34A'),
   'B 8899 MLG', 'Agus Salim', '081355667788', 'CB150R',
   DATE_ADD(CURDATE(), INTERVAL 3 DAY), '09:00:00', 1, 'Servis rutin bulanan', 'pending',
   DATE_SUB(NOW(), INTERVAL 5 HOUR)),

  -- ===== H+4 (agar tabel & pagination teruji) =====
  (CONCAT('AHASS-', DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 4 DAY),'%y%m%d'), '-N56B'),
   'B 4411 TGR', 'Rizky Ramadhan', '081233445566', 'Beat',
   DATE_ADD(CURDATE(), INTERVAL 4 DAY), '11:00:00', 3, NULL, 'pending',
   DATE_SUB(NOW(), INTERVAL 4 HOUR)),

  -- ===== MASA LALU — untuk grafik 7 hari & riwayat by plat =====
  (CONCAT('AHASS-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 DAY),'%y%m%d'), '-P82C'),
   'B 1199 PSG', 'Rina Marlina', '085699887766', 'Beat',
   DATE_SUB(CURDATE(), INTERVAL 1 DAY), '10:00:00', 1, 'Ganti oli + cek rantai', 'selesai',
   DATE_SUB(NOW(), INTERVAL 30 HOUR)),

  (CONCAT('AHASS-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 2 DAY),'%y%m%d'), '-Q47D'),
   'B 4477 JKT', 'Dedi Kurniawan', '081244556677', 'PCX',
   DATE_SUB(CURDATE(), INTERVAL 2 DAY), '13:00:00', 2, 'Servis lengkap + ganti busi iridium', 'selesai',
   DATE_SUB(NOW(), INTERVAL 52 HOUR)),

  -- Plat sama dengan data H-1 → memperkaya RIWAYAT by plat (2 kunjungan)
  (CONCAT('AHASS-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 3 DAY),'%y%m%d'), '-R19E'),
   'B 1199 PSG', 'Rina Marlina', '085699887766', 'Beat',
   DATE_SUB(CURDATE(), INTERVAL 3 DAY), '09:00:00', 3, NULL, 'selesai',
   DATE_SUB(NOW(), INTERVAL 74 HOUR)),

  -- Booking lama yang dibatalkan
  (CONCAT('AHASS-', DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 DAY),'%y%m%d'), '-S73F'),
   'F 2233 BGR', 'Tono Prasetyo', '081366778899', 'Vario 125',
   DATE_SUB(CURDATE(), INTERVAL 5 DAY), '14:00:00', 1, 'Berhalangan', 'dibatalkan',
   DATE_SUB(NOW(), INTERVAL 120 HOUR));

-- ------------------------------------------------------------
-- 📄 3. TEMPLATE SURAT (FR-13 — Generator Surat Otomatis)
-- ------------------------------------------------------------
INSERT INTO template_surat (jenis, kode_jenis, deskripsi, body_template, variabel_list, aktif) VALUES
(
  'Surat Penawaran Kerja Sama', 'PKS',
  'Penawaran kerja sama servis/pemasaran ke pihak lain',
  'Dengan hormat,\n\nMelalui surat ini kami {{nama_perusahaan}} — AHASS (Astra Honda Authorized Service Station) beralamat di Jl. Raya Contoh No. 88, ingin menawarkan kerja sama kepada Bapak/Ibu {{nama_kontak}} mengenai {{perihal}}.\n\nKami menyediakan layanan servis berkala, perbaikan, dan pemasangan part genuine Honda dengan tenaga mekanik bersertifikat serta suku cadang asli Honda. Kami yakin kerja sama ini akan memberikan manfaat bagi kedua belah pihak.\n\nBersama surat ini kami sampaikan penawaran kami, dan atas perhatian serta kerjasama Bapak/Ibu kami ucapkan terima kasih.\n\nHormat kami,\n{{nama_penandatangan}}\n{{jabatan}}',
  '[{"name":"nama_perusahaan","label":"Nama Perusahaan Tujuan","required":true},{"name":"nama_kontak","label":"Nama Kontak","required":true},{"name":"perihal","label":"Perihal Penawaran","required":true},{"name":"nama_penandatangan","label":"Nama Penandatangan","required":true},{"name":"jabatan","label":"Jabatan","required":true}]',
  1
),
(
  'Surat Undangan', 'UND',
  'Undangan rapat/acara ke instansi atau perorangan',
  'Yth. {{nama_penerima}}\ndi Tempat\n\nDengan hormat,\n\n{{nama_acara}} akan diselenggarakan pada:\n\n    Hari/Tanggal : {{tanggal_acara}}\n    Waktu        : {{waktu_acara}}\n    Tempat       : {{tempat}}\n\nSehubungan dengan hal tersebut, kami mengundang Bapak/Ibu untuk dapat hadir dalam acara tersebut. Mengingat pentingnya acara ini, kami sangat mengharapkan kehadiran Bapak/Ibu.\n\nAtas perhatian dan kehadiran Bapak/Ibu, kami mengucapkan terima kasih.\n\nHormat kami,\n{{nama_penandatangan}}\n{{jabatan}}',
  '[{"name":"nama_penerima","label":"Nama Penerima / Instansi","required":true},{"name":"nama_acara","label":"Nama Acara","required":true},{"name":"tanggal_acara","label":"Tanggal Acara","required":true},{"name":"waktu_acara","label":"Waktu Acara","required":true},{"name":"tempat","label":"Tempat","required":true},{"name":"nama_penandatangan","label":"Nama Penandatangan","required":true},{"name":"jabatan","label":"Jabatan","required":true}]',
  1
),
(
  'Surat Keterangan', 'KET',
  'Keterangan kerja/siswa/kendaraan untuk keperluan administrasi',
  'Yang bertanda tangan di bawah ini menerangkan bahwa:\n\n    Nama        : {{nama}}\n    Jabatan     : {{jabatan_or_keperluan}}\n    Keperluan   : {{keperluan}}\n\nBenar yang bersangkutan tersebut di atas adalah {{keperluan}} dan dapat dipertanggungjawabkan.\n\nDemikian surat ini dibuat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.\n\nHormat kami,\n{{nama_penandatangan}}\n{{jabatan_penandatangan}}',
  '[{"name":"nama","label":"Nama yang Diterangkan","required":true},{"name":"jabatan_or_keperluan","label":"Jabatan","required":true},{"name":"keperluan","label":"Keperluan","required":true},{"name":"nama_penandatangan","label":"Nama Penandatangan","required":true},{"name":"jabatan_penandatangan","label":"Jabatan Penandatangan","required":true}]',
  1
),
(
  'Surat Permohonan', 'PMH',
  'Permohonan izin/bantuan/kerjasama ke instansi',
  'Yth. {{nama_instansi}}\ndi Tempat\n\nDengan hormat,\n\n{{perihal}}\n\nSehubungan dengan hal tersebut di atas, kami mengajukan permohonan kepada Bapak/Ibu untuk {{isi_permohonan}}.\n\nDemikian permohonan ini kami sampaikan. Atas perhatian dan persetujuan Bapak/Ibu kami ucapkan terima kasih.\n\nHormat kami,\n{{nama_penandatangan}}\n{{jabatan}}',
  '[{"name":"nama_instansi","label":"Nama Instansi Tujuan","required":true},{"name":"perihal","label":"Perihal Permohonan","required":true},{"name":"isi_permohonan","label":"Isi Permohonan","required":true},{"name":"nama_penandatangan","label":"Nama Penandatangan","required":true},{"name":"jabatan","label":"Jabatan","required":true}]',
  1
);
