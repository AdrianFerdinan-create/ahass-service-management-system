-- ============================================================
-- SISTEM BOOKING SERVIS AHASS (Honda)
-- Schema Database MySQL
-- Main Dealer Anugerah Perdana — Job Test Web Programmer (PHP)
-- ============================================================
-- Jalankan file ini sekali untuk membuat seluruh struktur tabel:
--   mysql -u root < database/schema.sql
-- atau import via phpMyAdmin (Laragon) pada database `ahass_booking`.
-- Setelah schema, jalankan database/seed.sql untuk data demo.
-- ============================================================

CREATE DATABASE IF NOT EXISTS ahass_booking
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ahass_booking;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS riwayat_surat;
DROP TABLE IF EXISTS template_surat;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS paket_servis;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- 1. Paket Servis (FR-06)
-- ------------------------------------------------------------
CREATE TABLE paket_servis (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nama        VARCHAR(100) NOT NULL,
  deskripsi   TEXT,
  harga       DECIMAL(12,2) NOT NULL DEFAULT 0,
  aktif       TINYINT(1) DEFAULT 1,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. Bookings (FR-01, FR-02, FR-04, FR-07, FR-08)
--    - kuota per slot jam = 3 kendaraan (divalidasi di backend)
--    - status 'dibatalkan' TIDAK dihitung dalam kuota
-- ------------------------------------------------------------
CREATE TABLE bookings (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  kode_booking   VARCHAR(20)  NOT NULL UNIQUE,
  plat_nomor     VARCHAR(20)  NOT NULL,
  nama_pelanggan VARCHAR(100) NOT NULL,
  no_hp          VARCHAR(20)  NULL,
  tipe_motor     VARCHAR(50)  NOT NULL,
  tanggal        DATE NOT NULL,
  jam            TIME NOT NULL,
  paket_id       INT NOT NULL,
  catatan        TEXT NULL,
  status         ENUM('pending','dikonfirmasi','selesai','dibatalkan') DEFAULT 'pending',
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_booking_paket FOREIGN KEY (paket_id) REFERENCES paket_servis(id),
  UNIQUE KEY uniq_slot_plat (tanggal, jam, plat_nomor),
  INDEX idx_tanggal_jam (tanggal, jam),
  INDEX idx_status (status),
  INDEX idx_plat (plat_nomor)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. Template Surat (FR-13 — Generator Surat Otomatis)
-- ------------------------------------------------------------
CREATE TABLE template_surat (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  jenis          VARCHAR(100) NOT NULL,
  kode_jenis     VARCHAR(20)  NOT NULL,
  deskripsi      TEXT,
  body_template  TEXT NOT NULL COMMENT 'Isi surat dengan placeholder {{variabel}}',
  variabel_list  JSON NOT NULL COMMENT 'Daftar variabel: [{name, label, required}]',
  aktif          TINYINT(1) DEFAULT 1,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. Riwayat Surat yang pernah digenerate (FR-13)
-- ------------------------------------------------------------
CREATE TABLE riwayat_surat (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nomor_surat   VARCHAR(50) NOT NULL UNIQUE,
  template_id   INT NOT NULL,
  data_variabel JSON NOT NULL COMMENT 'Nilai variabel yang diisi user',
  body_final    TEXT NOT NULL COMMENT 'Hasil render surat setelah variabel diisi',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_riwayat_template FOREIGN KEY (template_id) REFERENCES template_surat(id),
  INDEX idx_nomor (nomor_surat)
) ENGINE=InnoDB;
