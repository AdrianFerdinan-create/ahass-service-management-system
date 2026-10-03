<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<!-- ═══════════════ SECTION: BOOKING ═══════════════ -->
<section id="booking" class="ahass-section">
	<div class="ahass-section__head">
		<span class="ahass-section__eyebrow">Langkah 1 — Daftar Servis</span>
		<h2>Form Booking Servis</h2>
		<p>Isi data kendaraan dan pilih slot waktu. Ketersediaan slot dicek ulang di server sebelum booking disimpan.</p>
	</div>

	<div class="row g-4">
		<!-- Form booking -->
		<div class="col-lg-7">
			<div class="ahass-card">
				<div class="ahass-card__head">
					<span class="ahass-card__icon"><i class="fa-solid fa-calendar-check"></i></span>
					<div>
						<h3>Data Booking</h3>
						<small>Kolom bertanda * wajib diisi</small>
					</div>
				</div>

				<div id="formBookingPlaceholder" class="ahass-empty">
					<span class="ahass-empty__icon"><i class="fa-solid fa-wrench"></i></span>
					<h4>Form booking sedang disiapkan</h4>
					<p>Validasi client + server, kode booking otomatis, dan konfirmasi struk akan aktif di tahap berikutnya.</p>
					<span class="next"><i class="fa-solid fa-arrow-right"></i> Tahap T2 — Form Booking</span>
				</div>
			</div>
		</div>

		<!-- Indikator slot (LIVE dari API) -->
		<div class="col-lg-5">
			<div class="ahass-card">
				<div class="ahass-card__head">
					<span class="ahass-card__icon"><i class="fa-solid fa-clock"></i></span>
					<div>
						<h3>Kuota Slot Hari Ini</h3>
						<small id="slotTanggalInfo">Memuat data…</small>
					</div>
					<button class="btn btn-sm btn-outline-secondary ms-auto rounded-pill px-3" id="btnRefreshSlot" title="Segarkan">
						<i class="fa-solid fa-rotate"></i>
					</button>
				</div>

				<div id="slotList">
					<div class="ahass-empty" style="padding:28px 16px">
						<span class="ahass-empty__icon"><i class="fa-solid fa-spinner fa-spin"></i></span>
						<h4>Mengambil kuota slot…</h4>
						<p>Data diambil real-time dari <code>/api/slot</code></p>
					</div>
				</div>

				<div class="d-flex gap-3 mt-3" style="font-size:12px;color:var(--muted)">
					<span><i class="fa-solid fa-circle" style="color:#16A34A"></i> Tersedia</span>
					<span><i class="fa-solid fa-circle" style="color:#F59E0B"></i> Hampir penuh</span>
					<span><i class="fa-solid fa-circle" style="color:#DC2626"></i> Penuh (3/3)</span>
				</div>
			</div>
		</div>
	</div>
</section>

<!-- ═══════════════ SECTION: DAFTAR BOOKING ═══════════════ -->
<section id="daftar" class="ahass-section">
	<div class="ahass-section__head">
		<span class="ahass-section__eyebrow">Langkah 2 — Pantau Booking</span>
		<h2>Daftar Booking</h2>
		<p>Semua booking tampil lengkap dengan filter tanggal, pencarian, paket, dan status — dimuat tanpa reload.</p>
	</div>

	<div class="ahass-card">
		<div id="tabelBooking" class="ahass-empty">
			<span class="ahass-empty__icon"><i class="fa-solid fa-table-list"></i></span>
			<h4>Tabel booking belum aktif</h4>
			<p>Filter, pagination server-side, modal detail, dan aksi ubah/batal status menyusul.</p>
			<span class="next"><i class="fa-solid fa-arrow-right"></i> Tahap T3 — Tabel &amp; Aksi</span>
		</div>
	</div>
</section>

<!-- ═══════════════ SECTION: DASHBOARD ═══════════════ -->
<section id="dashboard" class="ahass-section">
	<div class="ahass-section__head">
		<span class="ahass-section__eyebrow">Langkah 3 — Ringkasan</span>
		<h2>Dashboard</h2>
		<p>Kartu statistik, grafik booking 7 hari, heatmap okupansi slot, dan riwayat servis by nomor polisi.</p>
	</div>

	<div class="ahass-card">
		<div id="dashboardBody" class="ahass-empty">
			<span class="ahass-empty__icon"><i class="fa-solid fa-chart-column"></i></span>
			<h4>Dashboard belum aktif</h4>
			<p>Statistik real-time, grafik Chart.js, heatmap 7 hari × 9 jam, dan pencarian riwayat by plat.</p>
			<span class="next"><i class="fa-solid fa-arrow-right"></i> Tahap T4 — Dashboard</span>
		</div>
	</div>
</section>

<!-- ═══════════════ SECTION: LAPORAN ═══════════════ -->
<section id="laporan" class="ahass-section">
	<div class="ahass-section__head">
		<span class="ahass-section__eyebrow">Langkah 4 — Analitik Bisnis</span>
		<h2>Laporan</h2>
		<p>Rekap booking per rentang tanggal, estimasi pendapatan, okupansi rata-rata, dan breakdown per paket.</p>
	</div>

	<div class="ahass-card">
		<div id="laporanBody" class="ahass-empty">
			<span class="ahass-empty__icon"><i class="fa-solid fa-print"></i></span>
			<h4>Laporan belum aktif</h4>
			<p>Rekap server-side + tombol Cetak dan Export CSV.</p>
			<span class="next"><i class="fa-solid fa-arrow-right"></i> Tahap T5 — Laporan</span>
		</div>
	</div>
</section>

<!-- ═══════════════ SECTION: SURAT ═══════════════ -->
<section id="surat" class="ahass-section">
	<div class="ahass-section__head">
		<span class="ahass-section__eyebrow">Langkah 5 — Administrasi</span>
		<h2>Generator Surat</h2>
		<p>Pilih jenis surat, isi variabel dinamis, klik Generate — surat berkop AHASS siap cetak dengan nomor otomatis.</p>
	</div>

	<div class="ahass-card">
		<div id="suratBody" class="ahass-empty">
			<span class="ahass-empty__icon"><i class="fa-solid fa-file-signature"></i></span>
			<h4>Generator surat belum aktif</h4>
			<p>4 template surat (Penawaran, Undangan, Keterangan, Permohonan) + riwayat surat.</p>
			<span class="next"><i class="fa-solid fa-arrow-right"></i> Tahap T5.5 — Generator Surat</span>
		</div>
	</div>
</section>
