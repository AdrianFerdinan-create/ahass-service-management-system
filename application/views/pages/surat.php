<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php $this->load->view('partials/page-head', [
	'menu'       => 'surat',
	'eyebrow'    => 'Administrasi Otomatis',
	'judul'      => 'Generator Surat',
	'deskripsi'  => 'Pilih jenis surat, isi variabel dinamis, klik Generate — surat berkop AHASS siap cetak dengan nomor otomatis.',
	'breadcrumb' => ['Generator Surat'],
]); ?>

<main class="container py-4 py-lg-5">

<!-- ═══════════ LANGKAH 1 — PILIH TEMPLATE ═══════════ -->
<section class="ahass-section">
	<div class="ahass-section__head">
		<span class="ahass-section__eyebrow">Langkah 1 — Pilih Template</span>
		<h2>Jenis Surat</h2>
		<p>Form variabel berubah otomatis sesuai pilihan — via AJAX tanpa reload.</p>
	</div>

	<div class="ahass-card">
		<div class="row g-3 align-items-end">
			<div class="col-lg-6">
				<label class="ahass-label" for="pilihTemplate">Jenis Surat <span class="req">*</span></label>
				<select class="ahass-input" id="pilihTemplate">
					<option value="">Memuat template…</option>
				</select>
			</div>
			<div class="col-lg-6">
				<div id="infoTemplate" class="ahass-help" style="font-size:13px">
					Template dimuat otomatis dari database.
				</div>
			</div>
		</div>
	</div>
</section>

<!-- ═══════════ LANGKAH 2 — ISI & PREVIEW ═══════════ -->
<section class="ahass-section">
	<div class="ahass-section__head">
		<span class="ahass-section__eyebrow">Langkah 2 — Isi &amp; Preview</span>
		<h2>Form Variabel &amp; Preview Surat</h2>
		<p>Nomor surat dibuat otomatis: <code>AHASS/[KODE-JENIS]/[ROMAWI]/[TAHUN]</code></p>
	</div>

	<div class="row g-4">
		<!-- Form variabel -->
		<div class="col-lg-5">
			<div class="ahass-card">
				<div class="ahass-card__head">
					<span class="ahass-card__icon"><i class="fa-solid fa-pen-to-square"></i></span>
					<div><h3>Form Variabel</h3><small>Dinamis sesuai jenis surat</small></div>
				</div>

				<div id="formSurat">
					<div class="ahass-empty">
						<span class="ahass-empty__icon"><i class="fa-solid fa-spinner fa-spin"></i></span>
						<h4>Memuat template…</h4>
						<p>Mengambil daftar variabel dari server.</p>
					</div>
				</div>

				<div class="mb-3" id="wrapTanggalSurat" style="display:none">
					<label class="ahass-label" for="tanggalSurat">Tanggal Surat</label>
					<input type="date" class="ahass-input" id="tanggalSurat">
					<div class="ahass-help">Otomatis hari ini — boleh diubah.</div>
				</div>

				<button type="button" class="ahass-btn ahass-btn--primary w-100 justify-content-center" id="btnGenerateSurat" style="display:none">
					<i class="fa-solid fa-wand-magic-sparkles"></i><span>Generate Surat</span>
				</button>
			</div>
		</div>

		<!-- Preview -->
		<div class="col-lg-7">
			<div class="ahass-card">
				<div class="ahass-card__head">
					<span class="ahass-card__icon"><i class="fa-solid fa-eye"></i></span>
					<div><h3>Preview Surat</h3><small>Kop AHASS · nomor &amp; tanggal otomatis · tanda tangan</small></div>
				</div>

				<div id="previewSurat">
					<div class="ahass-empty">
						<span class="ahass-empty__icon"><i class="fa-solid fa-envelope-open"></i></span>
						<h4>Belum ada surat digenerate</h4>
						<p>Pilih jenis surat, isi variabel, lalu klik <b>Generate Surat</b>.</p>
					</div>
				</div>

				<div id="aksiSurat" class="d-none mt-3 gap-2 flex-wrap">
					<button type="button" class="ahass-btn ahass-btn--primary" id="btnCetakSurat">
						<i class="fa-solid fa-print"></i> Cetak Surat
					</button>
					<button type="button" class="ahass-btn" style="background:#fff;border:1.5px solid var(--line);color:var(--ink-2)" id="btnSuratBaru">
						<i class="fa-solid fa-plus"></i> Surat Baru
					</button>
					<small class="w-100" style="color:var(--muted);font-size:12.5px">
						Surat otomatis tersimpan ke riwayat saat digenerate.
					</small>
				</div>
			</div>
		</div>
	</div>
</section>

<!-- ═══════════ LANGKAH 3 — RIWAYAT ═══════════ -->
<section class="ahass-section">
	<div class="ahass-section__head">
		<span class="ahass-section__eyebrow">Langkah 3 — Arsip</span>
		<h2>Riwayat Surat</h2>
		<p>Surat yang pernah dibuat tersimpan di database — bisa dibuka kembali dan dicetak ulang.</p>
	</div>

	<div class="ahass-card">
		<div id="riwayatLoading">
			<div class="ahass-empty">
				<span class="ahass-empty__icon"><i class="fa-solid fa-spinner fa-spin"></i></span>
				<h4>Memuat riwayat…</h4>
			</div>
		</div>

		<div id="tabelRiwayat" class="table-responsive d-none">
			<table class="ahass-table">
				<thead>
					<tr>
						<th>Nomor Surat</th>
						<th>Jenis</th>
						<th>Tanggal</th>
						<th>Isi (ringkas)</th>
						<th class="text-end">Aksi</th>
					</tr>
				</thead>
				<tbody id="tbodyRiwayat"></tbody>
			</table>
		</div>

		<div id="riwayatKosong" class="ahass-empty d-none">
			<span class="ahass-empty__icon"><i class="fa-solid fa-box-archive"></i></span>
			<h4>Belum ada surat dibuat</h4>
			<p>Surat yang Anda generate akan otomatis tersimpan di sini.</p>
		</div>
	</div>
</section>

</main>
