# PRD — Sistem Booking Servis AHASS (Honda)

> **Product Requirements Document** — versi 2.0 (diperkaya)
> **Posisi:** Web Programmer (PHP) · **Job Test:** Main Dealer Anugerah Perdana
> **Status:** Siap dieksekusi setelah approval akhir

---

## 1. Konteks Proyek

| Item | Detail |
|---|---|
| Nama sistem | Sistem Booking Servis AHASS |
| Tujuan | Mencatat pendaftaran servis pelanggan, ketersediaan slot jam, dan pemilihan paket servis/part |
| Batas waktu | Senin, 5 Oktober 2026, 14.00 WITA |
| Target submit | **Sabtu, 3 Oktober 2026 malam** (deadline resmi Senin 5 Okt 14.00 WITA → buffer 2 hari) |
| Framework | **CodeIgniter 3** |
| Database | **MySQL** |
| Frontend | HTML/CSS/JS + **jQuery + AJAX** (tanpa reload), Bootstrap 5 |
| Desain | **Korporat AHASS** — merah Honda, putih, bersih, responsif |
| Kode booking | Unik per booking + halaman konfirmasi/struk cetak |
| Rentang booking | Hari ini s/d H+7 |
| Data demo | Seed cerdas (slot hampir penuh, aneka status) |
| Fitur pembeda | Laporan otomatis (cetak + CSV) · saran slot alternatif · heatmap 7 hari · riwayat by plat |

### 🗺️ Peta Soal Ujian → Fitur → Bukti

| # | Poin di Lembar Ujian | FR | Bukti di Aplikasi |
|---|---|---|---|
| 1 | Form: Plat, Nama, Tipe Motor, Jam, Paket | FR-01 | Form utama |
| 2 | Validasi slot backend maks 3, error via AJAX | FR-02 | Toast merah + JSON 409 (seed slot 10.00 sudah 2/3 → penguji tinggal booking ke-4) |
| 3 | Tabel list booking + filter | FR-04 | Section Daftar Booking |
| 4 | jQuery/AJAX tanpa reload | semua FR | Network tab: semua **aksi data** (submit, filter, aksi status) tidak memicu full reload |
| 5 | CodeIgniter 3 | Arsitektur | Struktur folder di §7 |
| 6 | PostgreSQL / MySQL | Skema | `database/schema.sql` |
| 7 | Repo GitHub public | Deliverable | §11 |
| 8 | README instruksi menjalankan | Deliverable | §11 blueprint |

**Nilai plus (di luar soal, untuk menang):** FR-03 indikator kuota real-time · FR-05 dashboard · FR-07 kode booking + struk · FR-08 detail booking · **FR-09 laporan otomatis · FR-10 saran slot alternatif · FR-11 heatmap 7 hari · FR-12 riwayat by plat** · seed demo cerdas · desain korporat setara produk komersial · test case table.

---

## 2. Tujuan & Kriteria Keberhasilan

**Tujuan produk:** Pelanggan bisa daftar servis online tanpa antre, dan petugas AHASS punya daftar booking harian yang jelas.

**Kriteria lulus ujian (Definition of Done):**
1. ✅ Form booking tersimpan via AJAX tanpa reload halaman
2. ✅ Slot maksimal 3 kendaraan per jam — ke-4 penerima **error JSON** dari backend
3. ✅ Tabel list booking tampil + berfungsi filter
4. ✅ `schema.sql` tersedia di repo
5. ✅ `README.md` berisi instruksi menjalankan aplikasi
6. ✅ Repo GitHub public

**Kriteria menang (diferensiasi vs 10 pesaing):**
- Desain setara produk komersial, bukan tugas kuliah
- Indikator sisa kuota slot real-time
- Dashboard ringkas + statistik
- Keamanan & validasi ganda (client + server)
- README profesional: screenshot, ERD, struktur folder
- Kode booking unik + struk cetak
- Seed demo cerdas agar penguji melihat semua skenario dalam 1 menit

---

## 3. Pengguna

| Pengguna | Kebutuhan |
|---|---|
| Pelanggan (public) | Pilih tanggal/jam, isi data diri, pilih paket, dapat konfirmasi |
| Admin/Petugas AHASS | Melihat & memfilter daftar booking, memantau slot penuh |

> Aplikasi tanpa login (di luar scope) — fokus sesuai spek tugas. Semua fitur tetap aman dari CSRF & XSS.

---

## 4. Scope

### IN-SCOPE
- Form booking (AJAX submit)
- Validasi slot backend (maks 3/slot jam)
- Tabel list booking + filter (tanggal, pencarian, paket, status)
- Indikator kuota slot real-time
- Manajemen paket servis (data awal via seed)
- Status booking: `pending`, `dikonfirmasi`, `selesai`, `dibatalkan` (dibatalkan → slot kembali tersedia)
- Kode booking unik per transaksi + halaman konfirmasi/struk (cetak)
- Rentang booking: hari ini s/d H+7
- **Fitur pembeda (urutan setelah inti ujian jalan 100%):** laporan otomatis + cetak + CSV, saran slot alternatif saat penuh, heatmap okupansi 7 hari, riwayat servis by plat
- Desain responsif korporat AHASS
- `schema.sql`, `README.md`, seed data demo

### OUT-SCOPE (tidak dikerjakan — jaga fokus & deadline)
- Login / autentikasi pengguna
- Pembayaran online
- Notifikasi email/WA
- Multi-cabang / multi-bengkel
- Modul inventaris part
- Share WhatsApp / QR code pada struk (struk dicetak langsung dari sistem)
- Reschedule booking online
- Notifikasi otomatis eksternal

---

## 5. Fitur & Acceptance Criteria

### FR-01 Form Booking (AJAX)
**Input:** Plat Nomor*, Nama Pelanggan*, Tipe Motor* (dropdown: Beat, Vario, PCX, Supra X, CB150R, dll), Tanggal* (hari ini s/d H+7), Jam Servis* (dropdown slot), Paket Servis* (dropdown), Catatan (opsional), No. HP (opsional).

**Acceptance criteria:**
- Submit via `$.ajax` POST — halaman **tidak reload**
- Validasi client-side: required field, pola plat (contoh: `B 1234 ABC`), maks nama 100 char
- Validasi server-side ulang (client bisa di-bypass)
- Sukses → toast hijau + form reset + tabel & slot auto-refresh
- Gagal → toast merah menampilkan pesan error dari server
- Plat otomatis di-uppercase & di-trim
- Tanggal terbatas hari ini s/d H+7 (`min`/`max` + validasi server ulang)
- Saat sukses: sistem membuat **kode booking unik** (format `AHASS-YYMMDD-XXXX`) → tampil di konfirmasi, tabel, dan struk

### FR-02 Validasi Slot Backend (WAJIB)
**Aturan bisnis:**
```
kuota per slot = 3 kendaraan
hitung = SELECT COUNT(*) FROM bookings
         WHERE tanggal = ? AND jam = ?
         AND status NOT IN ('dibatalkan')
if hitung >= 3 → HTTP 409, JSON { success:false, message:"Slot jam 10.00 sudah penuh (3/3)..." }
```
- Validasi di **server** (bukan hanya menyembunyikan dropdown)
- Respon format JSON dengan field `success`, `message`
- Booking ganda (plat sama, tanggal & jam sama) ditolak → pesan jelas
- Slot penuh otomatis tidak bisa dipilih di UI (dropdown disabled + label "PENUH")
- **Anti race condition:** cek kuota + INSERT dalam **satu transaksi DB** (`SELECT COUNT ... FOR UPDATE` lalu insert) — dua submit di milidetik bersamaan tetap menghasilkan maksimal 3 terisi

### FR-03 Indikator Kuota Real-time
- Endpoint `GET /api/slots?tanggal=...` mengembalikan tiap jam: `kapasitas`, `terisi`, `sisa`
- UI menampilkan progress bar per slot: `1/3`, `2/3`, `PENUH`
- Refresh otomatis setelah submit booking

### FR-04 Tabel List Booking + Filter
- Kolom: No, Tanggal, Jam, Plat, Nama, Tipe Motor, Paket, Harga, Status, Aksi
- Filter: **tanggal** (date picker), **pencarian** (plat/nama), **paket**, **status**
- Dimuat via AJAX saat halaman dibuka & saat filter berubah (tanpa reload)
- Aksi: ubah status, batalkan booking (dengan konfirmasi SweetAlert)
- Pagination server-side (10/halaman) + info total data
- Empty state yang informatif

### FR-05 Dashboard Ringkas
- Kartu statistik: Booking hari ini, Slot terisi, Pendapatan estimasi, Booking batal
- Grafik sederhana booking 7 hari terakhir (Chart.js)
- Slot occupancy hari ini (bar per jam)

### FR-06 Paket Servis
Tabel `paket_servis` (seed):
| Paket | Harga | Isi |
|---|---|---|
| Servis Ringan | 85.000 | Ganti oli, cek rem, cek rantai |
| Servis Lengkap | 175.000 | Servis ringan + ganti busi + cek CVT |
| Ganti Oli | 60.000 | Ganti oli mesin + filter |
| Paket Part (pilih sendiri) | Sesuai part | Klaim, Kampas Rem, V-Belt, dll |

### FR-07 Kode Booking & Konfirmasi/Struk
- Setiap booking sukses menghasilkan kode unik `AHASS-YYMMDD-XXXX` (XXXX = alfanumerik random, unik di DB)
- Setelah submit sukses: **kartu konfirmasi** tampil berisi kode booking, ringkasan (tanggal, jam, plat, tipe, paket, estimasi harga), tombol "Lihat Struk" & "Booking Lagi"
- **Struk:** modal ala nota bengkel + tombol Cetak (`window.print()` + `@media print` — elemen layar disembunyikan)
- Kode booking bisa dicari di filter pencarian tabel

### FR-08 Detail Booking
- Klik baris / tombol "Detail" → **modal** menampilkan data lengkap + info status + waktu dibuat
- Tombol aksi di dalam modal: Ubah Status, Batalkan (konfirmasi SweetAlert: *"Batalkan booking ini? Slot akan kembali tersedia."*)
- Semua aksi via AJAX → toast → refresh tabel & indikator slot otomatis

### FR-09 Laporan Otomatis & Cetak/CSV
- Section **Laporan** (nav item) dengan filter **rentang tanggal** (default 7 hari terakhir)
- Isi rekap: total booking, booking selesai/dibatalkan, **estimasi pendapatan**, okupansi rata-rata slot, **breakdown per paket** (tabel + bar chart)
- Tombol **Cetak Laporan** (`@media print` — rapi ala laporan bengkel) dan **Export CSV** (unduh tanpa library berat)
- Semua angka dihitung server-side via agregasi query (bukan JS mentah)
- *Nilai jual:* penguji dari Main Dealer — ini menunjukkan mikir **bisnis bengkel**, bukan sekadar CRUD

### FR-10 Saran Slot Alternatif Saat Penuh
- Respon 409 tidak berhenti di "penuh" — body JSON menyertakan `alternatives: [{jam, sisa}]` (maks 3 jam kosong terdekat pada tanggal sama)
- UI: toast/modal error menampilkan **tombol jam alternatif** → klik → dropdown jam berpindah otomatis, user tinggal submit ulang
- *Nilai jual:* UX level produk asli — pesaing umumnya hanya menampilkan error mentah

### FR-11 Heatmap Okupansi 7 Hari
- Grid **7 hari (hari ini + 6 ke depan) × 9 jam** di section Dashboard
- Warna sel: hijau (0/3) · oranye (2/3) · merah (PENUH) · abu (jam sudah lewat / hari lampau)
- Ada legend + tooltip (`10.00 · Rab · 2/3 terisi`)
- Klik sel → modal daftar booking slot tersebut
- Endpoint agregasi sekali panggil untuk seluruh grid (hemat request)

### FR-12 Riwayat Servis by Plat
- Input plat + tombol "Cari Riwayat" → daftar booking plat tersebut (termasuk yang `selesai`)
- Ringkasan: total kunjungan, tipe motor, terakhir servis kapan
- Plat tidak ditemukan → empty state ramah (bukan error merah)
- Mencegah user mengira sistem kehilangan data pelanggan lama

### 🔄 Alur Status Booking (State Flow)

```
pending ──(petugas konfirmasi)──▶ dikonfirmasi ──(servis selesai)──▶ selesai
   │                                   │
   └──────────(batalkan)───────────────┴────▶ dibatalkan
```

| Transisi | Aksi | Efek kuota slot |
|---|---|---|
| `pending` → `dikonfirmasi` | Tombol Konfirmasi | Tetap menghitung kuota |
| `dikonfirmasi` → `selesai` | Tandai Selesai | Tetap menghitung kuota |
| `pending`/`dikonfirmasi` → `dibatalkan` | Batalkan (konfirmasi) | **Kuota kembali tersedia** |

- Hanya status `pending`, `dikonfirmasi`, `selesai` yang dihitung kuota; `dibatalkan` tidak
- Semua perubahan status via AJAX + konfirmasi + toast + refresh

---

## 6. Desain UI / UX

### Style Guide AHASS
| Token | Nilai |
|---|---|
| Primary (Honda Red) | `#E4002B` |
| Secondary | `#1A1A1A` (dark) / `#FFFFFF` |
| Accent netral | `#F5F6F8`, `#E5E7EB` |
| Sukses | `#16A34A` · Error = `#DC2626` · Warning = `#F59E0B` |
| Font | Inter / System UI |
| Framework CSS | Bootstrap 5 (custom theme) |
| Border radius | 12px (kartu), 8px (input) |
| Shadow | halus, modern |

### Layout
- **Header** sticky: logo teks "AHASS — Booking Servis Online" + nav (Booking, Daftar Booking, Dashboard, Laporan, Surat) — **tiap nav menu = halaman tersendiri**
- **Hero + Form Booking** sebagai section utama (sidebar kanan: indikator slot)
- **Section Daftar Booking**: filter bar di atas, tabel di bawah
- **Footer**: "Salam Satu HATI" + kredit
- **Responsif**: mobile-first, tabel bisa scroll horizontal
- Elemen: kartu berbayang, ikon FontAwesome, toast notifikasi, loading spinner saat AJAX

### 🖼️ Wireframe Utama (Layout)

```
┌─────────────────────────────────────────────────────────┐
│ HEADER (sticky, putih, shadow halus)                    │
│ [◆ AHASS]  Booking · Daftar Booking · Dashboard · Laporan │
├─────────────────────────────────────────────────────────┤
│ HERO (gradasi merah)  "Booking Servis Online"           │
│ "Salam Satu HATI — Tanpa Antre, Datang Tepat Waktu"     │
├──────────────────────────────┬──────────────────────────┤
│ 📋 FORM BOOKING (kartu)      │ ⏰ SLOT HARI INI (kartu)  │
│ Tanggal*  [▼ H+7]            │ 08.00  ▓▓▓░  2/3         │
│ Jam*      [▼ pilih slot]     │ 09.00  ▓░░░  1/3         │
│ Plat *    [B 1234 ABC]       │ 10.00  ▓▓▓   PENUH 🔒    │
│ Nama *    [...........]      │ 11.00  ░░░░  0/3         │
│ Tipe Motor* / Paket* / HP    │ ...(progress per jam)     │
│ [ 🟢 Booking Sekarang ]     │ refresh otomatis          │
├──────────────────────────────┴──────────────────────────┤
│ ✅ KONFIRMASI (muncul setelah sukses)                    │
│ Kode: AHASS-261005-7F3A   [🧾 Lihat Struk] [Booking Lagi]│
├─────────────────────────────────────────────────────────┤
│ 📊 DASHBOARD: [Booking Hari Ini] [Slot Terisi] [Estimasi│
│ Pendapatan] [Dibatalkan] + grafik 7 hari (Chart.js)      │
│ 🗓️ HEATMAP 7 HARI × JAM · 🔎 RIWAYAT by plat             │
│ 📄 LAPORAN: filter rentang → rekap → [🖨 Cetak] [CSV]     │
├─────────────────────────────────────────────────────────┤
│ 🔍 FILTER: [📅 tanggal] [🔎 plat/nama/kode] [paket][status]│
│ TABEL: Kode | Tgl | Jam | Plat | Nama | Motor | Paket    │
│        | Status | Aksi (Detail · Ubah · Batalkan)        │
│ < pagination >                                           │
├─────────────────────────────────────────────────────────┤
│ FOOTER (dark)  © 2026 AHASS · Salam Satu HATI            │
└─────────────────────────────────────────────────────────┘
```

### 📑 Catatan: Layout Dipecah Jadi 5 Halaman (multi-page)
Wireframe di atas adalah **keseluruhan konten aplikasi**. Secara implementasi dipecah:

| Halaman | URL | Isi (bagian wireframe) |
|---|---|---|
| **Booking** | `/` | Hero + Form Booking + Slot Hari Ini + kartu konfirmasi (setelah submit) |
| **Daftar Booking** | `/daftar` | Filter bar + tabel + pagination + modal detail/struk |
| **Dashboard** | `/dashboard` | Kartu statistik + grafik 7 hari + heatmap + riwayat by plat |
| **Laporan** | `/laporan` | Filter rentang + rekap + breakdown per paket + Cetak/CSV |
| **Surat** | `/surat` | Pilih template + form variabel dinamis + preview + riwayat |

Aturan tetap: **semua aksi data via AJAX tanpa reload**; reload hanya saat pindah halaman.
Header sticky & footer tampil konsisten di kelima halaman; nav item diberi state `active` sesuai halaman aktif.

### 🔤 Tipografi

| Elemen | Ukuran | Weight | Catatan |
|---|---|---|---|
| H1 (hero) | 28/34px | 700 | putih di atas hero merah |
| H2 (section) | 22px | 700 | |
| H3 (kartu) | 18px | 600 | |
| Body | 15px | 400 | line-height 1.6 |
| Small / help | 13px | 400 | abu `#6B7280` |
| Label form | 12px | 600 | UPPERCASE, letter-spacing .04em |
| Angka statistik | 28px | 700 | tabular-nums |

Font: **Inter** (Google Fonts) → fallback `system-ui, sans-serif`.

### 📐 Spacing & Radius
- Skala spasi: `4 / 8 / 12 / 16 / 24 / 32 / 48px`
- Padding kartu: **24px** · Gap antar kartu: **24px** · Jarak section: **48–64px**
- Radius: kartu **12px**, input & tombol **8px**, badge **pill (999px)**
- Shadow kartu: `0 4px 16px rgba(0,0,0,.06)`; hover: `0 8px 24px rgba(0,0,0,.10)`

### 🧱 Komponen Inventory (wajib konsisten)

| Komponen | Varian |
|---|---|
| Tombol | primary (merah `#E4002B`), outline, ghost, danger · ukuran sm/md · state loading (spinner) |
| Input | default, **focus ring** merah 3px transparan, **error** (border merah + pesan di bawah) |
| Badge status | `pending` abu · `dikonfirmasi` biru · `selesai` hijau · `dibatalkan` merah (soft/rounded) |
| Toast | success/warning/error · slide-in kanan-atas · auto-dismiss 4 dtk |
| Modal | detail booking, struk/cetak, konfirmasi batalkan (SweetAlert2) |
| Progress bar slot | `<2` hijau `#16A34A` · `=2` oranye `#F59E0B` · `PENUH` merah + ikon gembok |
| Skeleton | shimmer untuk loading tabel (bukan spinner kosong) |
| Empty state | ikon + pesan "Belum ada booking" + tombol Reset Filter |
| Kartu statistik | ikon, angka besar, label, delta kecil |

### ✨ State & Mikro-interaksi
- Transisi `0.2s ease` pada tombol, kartu, baris tabel
- Hover: kartu naik shadow · baris tabel highlight `#F9FAFB`
- **Focus ring terlihat** (aksesibilitas keyboard)
- Disabled: slot penuh `opacity:.5` + `cursor:not-allowed`
- Skeleton shimmer saat load tabel · spinner pada tombol submit
- Progress bar animasi lebar `0.4s` · toast slide-in
- **Tanpa layout shift** saat data dimuat (hal tinggi kartu statis)

### 📱 Responsif
- Breakpoint: **576 / 768 / 992 / 1200px** (mobile-first)
- Form 2 kolom → 1 kolom di <768px
- Tabel: horizontal scroll + sticky kolom Plat di mobile
- Kartu slot jadi grid 2 kolom di mobile · header jadi navbar hamburger

### 🏁 Kelengkapan "Produk" (bukan tugas kuliah)
- `<title>` + meta description + **favicon** (H merah) + OG tags
- Route/API tidak ditemukan → JSON 404 rapi `{success:false, message:"Endpoint tidak ditemukan"}`
- Debug mode **OFF** saat submit · error CI3 tanpa traceback ke user
- Print stylesheet khusus struk
- Footer: "Salam Satu HATI" + kredit

### Prinsip UX
1. Progressif: slot penuh terlihat *sebelum* user submit
2. Feedback selalu ada (loading, sukses, gagal)
3. Tidak ada halaman blank — semua interaksi AJAX
4. Aksesibilitas: label form, kontras warna memadai

---

## 7. Arsitektur Teknis

```
ahass-booking/
├── application/
│   ├── controllers/
│   │   ├── Welcome.php          # Halaman Booking (form + slot live) → /
│   │   ├── Daftar.php           # Halaman Daftar Booking → /daftar
│   │   ├── Dashboard.php        # Halaman Dashboard → /dashboard
│   │   ├── Laporan.php          # Halaman Laporan → /laporan
│   │   ├── Surat.php            # Halaman Generator Surat → /surat
│   │   └── Api/
│   │       ├── Booking.php      # CRUD booking + validasi slot
│   │       ├── Slot.php         # Kuota per jam
│   │       └── Paket.php        # Daftar paket
│   ├── models/
│   │   ├── Booking_model.php    # Termasuk hitungKuota() - FOR UPDATE
│   │   └── Paket_model.php
│   ├── views/
│   │   ├── layout/header.php, footer.php
│   │   ├── pages/home.php       # SPA: form + tabel + dashboard
│   │   └── partials/ (form, tabel, dashboard)
│   └── config/ (database, routes, csrf)
├── database/
│   ├── schema.sql               # ← wajib diserahkan
│   └── seed.sql
├── assets/
│   ├── css/app.css              # theme AHASS
│   └── js/app.js                # AJAX logic
├── README.md
└── screenshots/
```

**Pola: MULTI-PAGE + AJAX** (keputusan user, menggantikan SPA 1-halaman):
- **5 halaman terpisah** — `/` (Booking), `/daftar`, `/dashboard`, `/laporan`, `/surat`
- Tiap halaman punya URL sendiri → rapi, bisa di-bookmark, konten fokus & ringan
- Page header compact (breadcrumb + judul) di setiap halaman selain landing
- Nav aktif otomatis per halaman
- **Semua aksi data TETAP via AJAX tanpa reload** (`/api/*` + render jQuery): submit booking, filter tabel, ubah/batal status, muat grafik, laporan, surat. Reload hanya terjadi saat pindah menu (wajar untuk multi-page).

**Keamanan (wajib):**
- CSRF protection CI3 (token pada setiap POST)
- Prepared statement / query binding (hindari SQL injection)
- `htmlspecialchars()` saat render (hindari XSS)
- Server-side validation (CI3 Form_validation)
- Header `Content-Type: application/json` pada semua respon API

---

## 8. Skema Database (MySQL)

```sql
CREATE TABLE paket_servis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  deskripsi TEXT,
  harga DECIMAL(12,2) NOT NULL DEFAULT 0,
  aktif TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode_booking VARCHAR(20) NOT NULL UNIQUE,
  plat_nomor VARCHAR(20) NOT NULL,
  nama_pelanggan VARCHAR(100) NOT NULL,
  no_hp VARCHAR(20) NULL,
  tipe_motor VARCHAR(50) NOT NULL,
  tanggal DATE NOT NULL,
  jam TIME NOT NULL,
  paket_id INT NOT NULL,
  catatan TEXT NULL,
  status ENUM('pending','dikonfirmasi','selesai','dibatalkan') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (paket_id) REFERENCES paket_servis(id),
  UNIQUE KEY uniq_slot_plat (tanggal, jam, plat_nomor),
  INDEX idx_tanggal_jam (tanggal, jam),
  INDEX idx_status (status)
) ENGINE=InnoDB;
```

**Jam operasional slot** (per 1 jam, kapasitas 3):
`08.00, 09.00, 10.00, 11.00, 12.00, 13.00, 14.00, 15.00, 16.00`

### 🌱 Seed Demo Cerdas

Tujuan: penguji melihat **semua skenario dalam <1 menit** tanpa isi data manual.

| Isi seed | Kegunaan demo |
|---|---|
| **Besok 10.00 → 2/3 terisi** | Penguji booking 1 lagi → langsung lihat **error 409 slot penuh** ⭐ |
| **Hari ini 13.00 → 3/3 PENUH** | Mendemonstrasikan dropdown disabled + label PENUH |
| Hari ini 09.00 → 1/3, status `dikonfirmasi` | Tampilan progress bar normal |
| Varian status: `pending`, `dikonfirmasi`, `selesai`, `dibatalkan` (masing-masing ≥1) | Badge warna & filter status |
| 1 booking `dibatalkan` di slot 11.00 | Bukti kuota kembali tersedia setelah batal |
| ~15 booking total, plat & nama Indonesia realistis, tipe motor bervariasi | Tabel & grafik terlihat hidup, pagination teruji |

- Tanggal seed dihitung **relatif terhadap `CURDATE()`** agar selalu relevan kapan pun dijalankan penguji
- 4 paket servis sesuai tabel FR-06
- Seed dibuat agar kode booking, pagination, dan grafik 7 hari langsung terisi

---

## 9. Kontrak API (JSON)

| Method | Endpoint | Deskripsi |
|---|---|---|
| POST | `/api/booking/create` | Simpan booking. **201** sukses / **409** slot penuh / **422** validasi |
| GET | `/api/booking/list?tanggal=&q=&paket=&status=&page=` | Daftar booking terfilter |
| GET | `/api/slot?tanggal=` | Kuota tiap jam `{jam, terisi, sisa, penuh}` |
| GET | `/api/paket` | Daftar paket servis |
| POST | `/api/booking/update-status` | Ubah/batalkan status |
| GET | `/api/booking/detail?id=` | Detail booking + struk (JSON) |
| GET | `/api/report?dari=&sampai=` | Rekap laporan (total, per paket, okupansi) |
| GET | `/api/slot/heatmap?mulai=` | Grid okupansi 7 hari × jam |
| GET | `/api/riwayat?plat=` | Riwayat booking by plat |
| GET | `/api/dashboard/stats` | Statistik ringkas |

**Contoh respon slot penuh:**
```json
{
  "success": false,
  "code": "SLOT_PENUH",
  "message": "Maaf, slot jam 10.00 sudah penuh (3/3). Silakan pilih jam lain.",
  "data": { "terisi": 3, "kapasitas": 3, "sisa": 0 }
}
```

---

## 10. Non-Functional Requirements

| Aspek | Requirement |
|---|---|
| Performa | Respon API < 500ms (data kecil, indexed) |
| Kompatibilitas | Chrome, Edge, Firefox (versi terbaru) |
| Responsif | Breakpoint: 576 / 768 / 992 / 1200 px |
| Keamanan | CSRF, prepared stmt, XSS escaping, server-side validation |
| Kode | Komentar pada logika bisnis penting, konsisten PSR-style, tanpa debug mode di rilis |

---

## 11. Deliverables

1. 📁 Repo **GitHub public** berisi source code CodeIgniter 3
2. 📄 `database/schema.sql` (+ `seed.sql` data demo)
3. 📖 `README.md` — **blueprint wajib, urutan ini:**
   1. Judul + badge + screenshot hero
   2. Tentang sistem + daftar fitur
   3. Tech stack (CodeIgniter 3, MySQL, jQuery/AJAX, Bootstrap 5)
   4. Requirements (PHP ≥ 7.4, MySQL 5.7+/8, XAMPP/Docker)
   5. Langkah instalasi bernomor (clone → import `database/schema.sql` → isi `application/config/database.php` → set `base_url` → jalankan)
   6. Kredensial DB default
   7. Data demo & **cara cepat uji skenario slot penuh**
   8. Tabel endpoint API
   9. ERD + screenshots
   10. Struktur folder
4. 📸 Folder `screenshots/` (halaman utama, slot penuh, filter, mobile)
5. 📧 Kirim link ke `ari13yustia@gmail.com` dan `anandamahdara@gmail.com`

---

## 12. Rencana Eksekusi — Sabtu 3 Okt (HARI INI), Sistem Bertahap

> 🎯 **Target: semua isi PRD dikerjakan hari ini, kumpulkan malam ini.** Deadline resmi tetap Senin 5 Okt 14.00 WITA → buffer 2 hari sebagai jaring pengaman.
>
> ▶️ **Cara kerja bersama AI:** kerjakan **1 tahap → tampilkan hasilnya → BERHENTI → tunggu perintah "lanjut" dari user → lanjut ke tahap berikutnya.** Dilarang mengerjakan lebih dari satu tahap dalam satu perintah.

### ⚙️ Aturan Main Tahapan (WAJIB)
1. **Dipandu perintah `LANJUT`** — AI **TIDAK BOLEH** otomatis masuk ke tahap berikutnya. Satu kali kerjakan **satu tahap saja**, lalu **BERHENTI dan laporkan hasilnya**. Tahap berikutnya **hanya dikerjakan setelah user mengetik perintah "lanjut"**. Jika user belum bilang lanjut → jangan lanjut.
2. **Setiap tahap WAJIB menghasilkan TAMPILAN nyata** — bukan hanya kode/logic/deskripsi. Tahap dianggap selesai hanya jika hasilnya sudah bisa **dilihat di browser**. Contoh:
   - T0 → halaman utama sudah tampil (header, hero, footer AHASS)
   - T1 → endpoint JSON sudah bisa diakses (bukan janji, tapi respon nyata)
   - T2 → form booking sudah terlihat, bisa diisi, tombol bisa diklik
   - T3 → tabel booking sudah tampil berisi data + filter jalan
   - dst. — **tidak boleh ada tahap tanpa hasil tampilan**
3. **Tidak ada 1 pun yang terlewat** — semua FR-01…FR-13 + seluruh deliverables (§11) wajib dikerjakan; progres ditandai di log tahap.
4. **Desain profesional & elegan sejak awal** — setiap view langsung dibuat sesuai standar §6 (tipografi, spacing, radius, shadow, state, mikro-interaksi). BUKAN tempelan di akhir.
5. **Jika situasi mendesak** — yang boleh di-drop hanya fitur pembeda (FR-09/11/12). Inti ujian (FR-01/02/04) + dokumentasi **tidak boleh**.
6. **Tiap selesai tahap** → laporkan **hasil nyata** (apa yang sudah tampil + test case yang PASS) → user periksa → user ketik **"lanjut"** → baru masuk tahap berikutnya. Bug di tahap awal DIPERBAIKI di tahap itu, tidak ditumpuk.

### 🗓️ Tahapan Hari Ini

| Tahap | Isi (FR) | Hasil Tampilan (wajib ada sebelum bilang "lanjut") | Estimasi |
|---|---|---|---|
| **T0 — Fondasi** | Setup CI3 + MySQL, `schema.sql`, `seed.sql`, konfig DB, routes, theme base | ✅ Halaman utama **terbuka di browser** dgn header/footer AHASS, tabel terisi seed | 1.5 jam |
| **T1 — Backend Inti** | FR-02 (create + validasi slot + transaksi), FR-04 list API, FR-03 slot API | ✅ Semua endpoint JSON **bisa dibuka/diuji langsung**: **201** sukses · **409** penuh (+alternatif) · **422** validasi · filter & kuota benar | 1.5 jam |
| **T2 — Form Booking** | FR-01, FR-07 (kode booking + kartu konfirmasi), FR-10 (saran slot alternatif) | ✅ Form booking **tampil & berfungsi** di browser: submit tanpa reload, kartu konfirmasi + struk muncul. TC-01, 02, 04, 05, 06, 16 **PASS** | 1.5 jam |
| **T3 — Tabel & Aksi** | FR-04 (tabel + filter + pagination + empty state), FR-08 (modal detail, ubah/batal status) | ✅ Tabel booking **tampil berisi data** + filter jalan + modal detail & aksi status. TC-07, 08, 09 **PASS** · slot kembali tersedia saat dibatalkan | 1.5 jam |
| **T4 — Dashboard, Heatmap, Riwayat** | FR-05 (kartu statistik + grafik), FR-11 heatmap, FR-12 riwayat by plat | ✅ Kartu statistik, **grafik 7 hari, heatmap & riwayat tampil** di browser. TC-18, 19 **PASS** · grid warna sesuai kuota | 1.5 jam |
| **T5 — Laporan** | FR-09 rekap + cetak + CSV | ✅ Section Laporan **tampil berisi angka rekap**, cetak & CSV jalan. TC-17 **PASS** · angka konsisten + print rapi | 1 jam |
| **T5.5 — Generator Surat** | FR-13 template surat + form variabel dinamis + generate + riwayat | ✅ Section Surat **tampil**: form dinamis, preview surat berkop AHASS, cetak & riwayat jalan. TC-20…24 **PASS** | 1.5 jam |
| **T6 — Polesan Elegan** | Review seluruh §6: tipografi, spacing, animasi, skeleton/empty/loading, responsif 375/768/1280, favicon+meta, keamanan (CSRF/XSS), debug OFF | ✅ Tampilan final **dicek visual di browser** (375/768/1280). TC-12, 13, 14 **PASS** | 1.5 jam |
| **T7 — Dokumentasi & Kumpul** | Run penuh TC-01…19, README blueprint, screenshots, push repo public, email ke 2 alamat | ✅ Screenshot halaman + checklist submit **tercentang semua** | 1.5 jam |

**Total ≈ 11 jam** → mulai 14.00 WITA ≈ selesai ~01.00 dini hari. Realistis dengan eksekusi bersama saya, bertahap, tanpa loncatan.

### 📌 Log Progres — dikerjakan SATU per SATU, lanjut hanya jika user perintah "lanjut"

> **🐛 Update 4 Okt 2026 — Perbaikan Bug (sebelum T5)**
> 1. ✅ `application/controllers/api/Report.php` **dibuat** — route `api/report` sebelumnya 404 (file tidak ada). FR-09 backend kini jalan: rekap, pendapatan, okupansi, breakdown per paket, deret harian.
> 2. ✅ `application/controllers/api/Surat.php` + `application/models/Surat_model.php` **dibuat** — route `api/surat/*` sebelumnya 404. FR-13 backend jalan: template, variabel dinamis, generate + nomor `AHASS/[KODE]/[ROMAWI]/[TAHUN]`, riwayat.
> 3. ✅ `application/core/MY_Exceptions.php` — semua error di `/api/*` kembali **JSON** (sebelumnya gagal CSRF balas halaman HTML, melanggar §7).
> 4. ✅ `Notfound` — 404 **halaman biasa** kini tampil halaman HTML ber-branding (`views/pages/404.php`); `/api/*` tetap JSON 404.
> 5. ✅ Data `template_surat` terkontaminasi mojibake (`—` → `ÔÇö`) akibat import seed lewat CLI Windows tanpa charset → sudah diperbaiki; `seed.sql` ditambah `SET NAMES utf8mb4` + catatan `--default-character-set=utf8mb4`.
> 6. ✅ `database.php` : `char_set` `utf8` → `utf8mb4` (antisipasi input 4-byte/emoji).
>
> **Hasil uji (4 Okt):** semua endpoint lulus — `GET /api/report|slot|booking/list|paket|riwayat|dashboard/stats|slot/heatmap|surat/template` → 200 · `POST surat/generate` → 201 + nomor unik · `POST booking/create` ke slot 3/3 → **409 + alternatives** (data tidak tersimpan) · kosong → 422 · tanpa CSRF → **403 JSON** · `/api/*` tak dikenal → 404 JSON · 5 halaman → 200 HTML · XSS `<script>` pada variabel surat → tersaring.
>
> **Status:** backend **FR-09 & FR-13 selesai**. View + JS Laporan (**T5**) dan Surat (**T5.5**) masih placeholder — lanjut ke tahap itu sesuai aturan "lanjut".

- [ ] **T0** Fondasi — CI3 + MySQL + schema + seed → *hasil: halaman tampil dgn header/footer AHASS*
- [ ] **T1** Backend — semua endpoint JSON lulus uji → *hasil: respon 201/409/422 nyata*
- [ ] **T2** Form booking + kode booking + saran alternatif → *hasil: form tampil & submit jalan tanpa reload*
- [ ] **T3** Tabel list + filter + detail + aksi status → *hasil: tabel tampil berisi data + filter jalan*
- [ ] **T4** Dashboard + heatmap + riwayat by plat → *hasil: kartu statistik, grafik & heatmap tampil*
- [x] **T5** Laporan otomatis + cetak + CSV → *hasil: section laporan tampil + cetak/CSV jalan* — ✅ **SELESAI 4 Okt**. View `pages/laporan.php` + JS FR-09 (filter rentang, 6 kartu rekap, bar chart per paket, tabel breakdown, Cetak via `#printArea`, Export CSV + BOM). Bukti: render headless Chrome → Total 14 · Selesai 4 · Batal 3 · Rp 1.130.000 · Okupansi 5,8% · tabel 3 paket (425rb+180rb+525rb=1,13jt ✓) · klik Cetak → `#printArea` terisi 4.750 char + `window.print()` terpanggil · klik CSV → toast sukses · rentang salah → pesan validasi. Screenshot: `screenshots/t5-laporan.png`
   - 🐛 **Bug lapangan (dilaporkan user): halaman terus "Memuat laporan…".** Diagnosis via log Apache: setelah `GET /laporan` **tidak ada satu pun request `/api/report`** → AJAX tidak pernah terkirim, padahal server hanya butuh **80ms**. Akar masalah: `app.js` diubah tapi URL tetap `?v=1` → browser memakai **JS lama dari cache** (heuristic freshness) yang belum punya fungsi `muatLaporan()`. **Perbaikan:** versi dinaikkan jadi `app.js?v=2` (aturan wajib: setiap ubah JS/CSS harus naik `?v=`) + peringatan otomatis di view bila data belum tampil dalam 8 detik (tombol "Muat Ulang"). Verifikasi: render **998 ms**, peringatan tidak muncul, tanpa hard-refresh (URL berubah → cache miss).
- [x] **T5.5** Generator Surat Otomatis (FR-13) → *hasil: form surat, preview & cetak tampil* — ✅ **SELESAI 4 Okt**. View `pages/surat.php` (3 langkah: pilih template → form variabel dinamis → preview berkop + riwayat) + JS FR-13 + CSS `.surat-*` (kertas, kop, tanda tangan & stempel placeholder) + `@media print`. **Aset dinaikkan: `app.js?v=3`, `app.css?v=4`** (pelajaran dari bug cache T5). Uji CDP headless: TC-21 ganti template **5 → 7 → 5 variabel** + info ikut terupdate ✓ · TC-22 generate kosong → **5 error tampil, 0 request terkirim** ✓ · TC-20 generate valid → kertas surat + kop + **`AHASS/PKS/X/2026`** + tanggal 4 Oktober 2026 + isi variabel + ttd/stempel ✓ · TC-23 cetak → `window.print()` + `#printArea` berisi kop & "Hormat kami" ✓ · TC-24 riwayat → klik Lihat buka ulang + toast ✓. Screenshot: `screenshots/t55-surat.png`. *Catatan: hasil awal "ganti template tetap 5" ternyata bug pada skrip uji (event sintetis tidak bubbles), bukan bug aplikasi — diverifikasi ulang dengan event bubbles.*
- [x] **T6** Polesan desain elegan + keamanan + responsif → *hasil: tampilan final rapi di 375/768/1280* — ✅ **SELESAI 4 Okt**. Audit dulu, baru ubah: (a) **audit responsif otomatis 5 halaman × 3 lebar = 15/15 tanpa overflow horizontal** (hamburger aktif <992, semua tabel dibungkus `.table-responsive`); (b) 2 item PRD §6 yang belum ada ditambahkan → **kartu slot jadi grid 2 kolom di mobile** + **kolom Plat sticky** (bukti: gulir 450px → Plat tetap di tepi 33px, kolom lain keluar −379px); (c) **debug OFF** → `ENVIRONMENT = production` (display_errors & db_debug mati); (d) **TC-12 XSS PASS** — booking nama `<script>alert(1)</script>` tersimpan, tampil sebagai teks literal (`&lt;script&gt;`), **0 dialog alert** selama uji; (e) **TC-13 PASS** — 375/768/1280 rapi, slot grid 2 kolom, sticky Plat; (f) **TC-14 PASS** — cetak dari kartu konfirmasi & modal detail → `#printArea` berisi struk, dengan media cetak: `#printArea` visible, header & tabel **hidden**; (g) audit escaping seluruh render pakai `ahassEsc` (satu-satunya konkatensi tanpa escape = toast kode booking server-generated, ditangani SweetAlert2 sebagai textContent); (h) smoke pasca-production: 5 halaman 200, 9 API 200, CSRF tanpa token → 403, log aplikasi kosong. Aset: **`app.css?v=5`**. Screenshot: `screenshots/responsive/` (15) + `booking-375-slotgrid.png` + `daftar-375-sticky.png`. Data uji dibersihkan (bookings kembali 18).
- [ ] **T7** Test case penuh + README + screenshots + push + email → *hasil: screenshot & dokumen lengkap* — 🔄 **4 Okt: 80% SELESAI**
  - ✅ **TC-01…24 dijalankan penuh → SEMUA PASS**, termasuk TC-10 race (6 request paralel → 1×201, 5×409, DB 3/3) dan TC-18 heatmap (63 sel + modal). Uji ulang TC-03/04/05/06/07/08/09/11/19 yang sebelumnya belum dibuktikan — semuanya PASS, data uji bersih (bookings 18, riwayat menyisakan 1 surat milik user sendiri yang TIDAK disentuh).
  - ✅ **`README.md` (513 baris, 14 bagian)** — judul+badge+hero, tentang sistem (penekanan: bukan booking biasa → penjagaan kuota, anti race, dashboard, laporan, generator surat), tech stack, kebutuhan, **instalasi Laragon bernomor**, konfigurasi default, **cara pakai lengkap per peran**, data demo + cara uji 409 dalam 30 detik, tabel API + contoh respon 409, ERD mermaid, **tabel test case TC-01…24**, **tabel pemenuhan ketentuan tugas**, struktur folder, screenshot, catatan teknis. Tone bahasa manusia profesional, tanpa jejak otomatisasi.
  - ✅ **Screenshot diperbarui**: `page-booking/daftar/dashboard/laporan/surat.png` (UI final, bukan placeholder) + 15 file `screenshots/responsive/` + `t5-laporan.png` + `t55-surat.png`. Seluruh path di README diverifikasi ada.
  - ✅ Verifikasi README: 8 fence kode genap, 1 mermaid, tidak ada typo tersisa, tidak ada kata terlarang, seluruh path gambar/file valid.
  - ⏳ **TERSISA (manual saat pengumpulan): push repo GitHub public + kirim email ke ari13yustia@gmail.com & anandamahdara@gmail.com**

### Test Case Table (wajib dilaporkan di README)

| ID | Skenario | Langkah | Ekspektasi | Pri |
|---|---|---|---|---|
| TC-01 | Booking valid | Isi form lengkap → klik Booking | Toast sukses, tabel & slot refresh, kode booking muncul, **tanpa reload** | High |
| TC-02 | Slot ke-4 (penuh) | Booking ke-4 di slot yang sudah 3/3 | Toast merah "sudah penuh (3/3)", HTTP **409**, data tidak tersimpan | High |
| TC-03 | Dropdown slot penuh | Buka form tanggal dengan slot 3/3 | Slot disabled + label PENUH | High |
| TC-04 | Booking ganda | Plat sama, tanggal & jam sama | Ditolak dengan pesan jelas | High |
| TC-05 | Form kosong | Klik Booking tanpa isi | Validasi client-side muncul, request tidak terkirim | High |
| TC-06 | Bypass client validation | Paksa kirim request kosong via DevTools | Server tolak (422) dengan pesan validasi | High |
| TC-07 | Filter | Ubah tanggal/paket/status | Tabel ter-update via AJAX | High |
| TC-08 | Pencarian | Ketik plat / nama / kode booking | Baris cocok tampil | High |
| TC-09 | Batalkan booking | Detail → Batalkan → konfirmasi | Status berubah, **slot kembali tersedia** di indikator | High |
| TC-10 | Race condition | 2 tab submit slot 2/3 bersamaan | Maksimal 3 terisi, tab ke-4 dapat error | Medium |
| TC-11 | Rentang tanggal | Coba pilih H+8 / kemarin | Ditolak `min`/`max` + server tolak | Medium |
| TC-12 | XSS | Isi nama `<script>alert(1)</script>` | Tampil literal, tidak dieksekusi | High |
| TC-13 | Responsif | Buka 375px / 768px / 1280px | Layout rapi, tabel scroll | Medium |
| TC-14 | Cetak struk | Detail → Lihat Struk → Cetak | Hanya struk tercetak (print CSS) | Medium |
| TC-15 | Repo & dokumen | Buka repo fresh | Public, `schema.sql` ada, README bisa diikuti dari nol | High |
| TC-16 | Saran slot alternatif | Booking di slot yang penuh | 409 + daftar 3 jam kosong, klik → jam berpindah otomatis | High |
| TC-17 | Laporan | Pilih rentang → muat → cetak → CSV | Angka rekap sesuai data, print rapi, CSV terunduh | High |
| TC-18 | Heatmap | Buka dashboard | Grid 7 hari tampil, warna sesuai kuota, klik sel → daftar booking | Medium |
| TC-19 | Riwayat by plat | Cari plat ada riwayat / plat tidak ada | Daftar muncul / empty state ramah | Medium |

### Checklist Sebelum Submit
- [ ] Semua TC High = PASS
- [ ] Repo public & `schema.sql` ada
- [ ] README + screenshot lengkap
- [ ] Email terkirim ke **dua alamat** sebelum 14.00 WITA

---

## 13. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Kehabisan waktu | Prioritas: FR-01/02/04 (wajib) → FR-07/08 + FR-10 (easy win cepat) → FR-03/05/09/11/12 (pembeda — boleh di-drop menjelang deadline, inti ujian TIDAK BOLEH) |
| Race condition slot penuh | `SELECT ... FOR UPDATE` / cek ulang dalam transaksi |
| Bug detik-detik akhir | Deadline internal **Sabtu malam**; Minggu–Senin hanya review, perbaikan minor, buffer kirim |
| Repo tidak terbaca penguji | README lengkap + screenshot |

---

*PRD ini dokumen hidup — boleh direvisi selama tidak mengubah hard requirement dari soal ujian.*

---

## 14. Fitur Tambahan — FR-13 Generator Surat Otomatis

### FR-13 Generator Surat Otomatis

**Deskripsi:**
Dealer tidak perlu membuat surat dari nol setiap kali. Mereka cukup memilih jenis/template surat, mengisi variabel dinamis (nama perusahaan tujuan, tanggal, keperluan, dll.), klik *Generate*, dan surat langsung terbentuk siap cetak.

**Jenis Surat (seed awal — bisa ditambah):**
| No | Jenis Surat | Variabel Dinamis |
|---|---|---|
| 1 | Surat Penawaran Kerja Sama | Nama Perusahaan Tujuan, Nama Kontak, Tanggal, Perihal Penawaran |
| 2 | Surat Undangan | Nama Penerima/Instansi, Acara, Tanggal & Waktu, Tempat |
| 3 | Surat Keterangan | Nama yang Diterangkan, Jabatan/Keperluan, Tanggal |
| 4 | Surat Permohonan | Nama Instansi Tujuan, Perihal Permohonan, Tanggal |

**Alur Penggunaan:**
1. User membuka section **Surat** (nav item)
2. Pilih jenis surat dari dropdown
3. Form variabel dinamis muncul otomatis sesuai jenis surat yang dipilih (via AJAX)
4. User mengisi variabel (nama perusahaan, tanggal, keperluan, dll.)
5. Klik **Generate Surat** → preview surat tampil di layar (modal/section)
6. Tombol **Cetak Surat** (`window.print()` + `@media print`) dan **Simpan ke Riwayat**
7. Riwayat surat yang pernah dibuat bisa dilihat kembali

**Acceptance Criteria:**
- Pilih jenis surat → form variabel berubah otomatis via AJAX (tanpa reload)
- Semua variabel wajib diisi — validasi client-side + server-side
- Preview surat tampil dengan kop surat AHASS (logo, nama dealer, alamat)
- Format surat rapi: tanggal otomatis terisi hari ini (bisa diubah), nomor surat auto-generate
- Nomor surat format: `AHASS/[KODE-JENIS]/[ROMAWI-BULAN]/[TAHUN]` (contoh: `AHASS/PKS/X/2026`)
- Cetak hanya menampilkan surat (elemen UI disembunyikan via `@media print`)
- Riwayat surat tersimpan di DB — bisa dibuka kembali, preview ulang, dan cetak ulang
- Desain preview surat: putih bersih, font formal, kop atas, tanda tangan & stempel placeholder di bawah

**Skema Database Tambahan:**
```sql
CREATE TABLE template_surat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  jenis VARCHAR(100) NOT NULL,
  kode_jenis VARCHAR(20) NOT NULL,
  deskripsi TEXT,
  body_template TEXT NOT NULL COMMENT 'Isi surat dengan placeholder {{variabel}}',
  variabel_list JSON NOT NULL COMMENT 'Daftar variabel: [{name, label, required}]',
  aktif TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE riwayat_surat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nomor_surat VARCHAR(50) NOT NULL UNIQUE,
  template_id INT NOT NULL,
  data_variabel JSON NOT NULL COMMENT 'Nilai variabel yang diisi user',
  body_final TEXT NOT NULL COMMENT 'Hasil render surat setelah variabel diisi',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (template_id) REFERENCES template_surat(id)
) ENGINE=InnoDB;
```

**Endpoint API Tambahan:**
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/surat/template` | Daftar semua template surat aktif |
| GET | `/api/surat/template?id=` | Detail template + daftar variabel (untuk render form dinamis) |
| POST | `/api/surat/generate` | Generate surat: terima variabel → render → simpan riwayat → kembalikan HTML surat |
| GET | `/api/surat/riwayat` | Daftar riwayat surat yang pernah dibuat |
| GET | `/api/surat/riwayat?id=` | Detail/body surat untuk preview & cetak ulang |

**Test Case Tambahan:**
| ID | Skenario | Langkah | Ekspektasi | Pri |
|---|---|---|---|---|
| TC-20 | Generate surat valid | Pilih template → isi semua variabel → Generate | Preview surat tampil lengkap, nomor surat auto, kop AHASS muncul | High |
| TC-21 | Form variabel dinamis | Ganti pilihan jenis surat | Form variabel berubah otomatis via AJAX tanpa reload | High |
| TC-22 | Validasi variabel kosong | Klik Generate tanpa isi variabel | Validasi muncul, request tidak terkirim | High |
| TC-23 | Cetak surat | Preview → Cetak | Hanya surat yang tercetak, UI tersembunyi | Medium |
| TC-24 | Riwayat surat | Generate surat → buka tab Riwayat | Surat muncul di riwayat, bisa dibuka & cetak ulang | Medium |

**Estimasi Pengerjaan:** ~1.5 jam (masuk di **T5.5** antara Laporan dan Polesan)

**Catatan:** Fitur ini berdiri sendiri, tidak mengganggu alur booking yang sudah ada. Template surat awal diisi via seed. Admin/petugas bisa langsung pakai tanpa perlu konfigurasi tambahan.
