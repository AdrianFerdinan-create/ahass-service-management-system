<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php $this->load->view('partials/page-head', [
	'menu'       => 'laporan',
	'eyebrow'    => 'Analitik Bisnis Bengkel',
	'judul'      => 'Laporan Otomatis',
	'deskripsi'  => 'Rekap booking per rentang tanggal, estimasi pendapatan, okupansi rata-rata slot, dan breakdown per paket servis.',
	'breadcrumb' => ['Laporan'],
]); ?>

<main class="container py-4 py-lg-5">

<!-- ═══════════ FILTER RENTANG ═══════════ -->
<section class="ahass-section">
	<div class="ahass-section__head">
		<span class="ahass-section__eyebrow">Filter Rentang</span>
		<h2>Periode Laporan</h2>
		<p>Default 7 hari terakhir. Semua angka dihitung <b>server-side</b> lewat agregasi query — bukan JavaScript mentah.</p>
	</div>

	<div class="ahass-card">
		<div class="row g-3 align-items-end">
			<div class="col-6 col-lg-3">
				<label class="ahass-label" for="lapDari">Dari Tanggal</label>
				<input type="date" class="ahass-input" id="lapDari">
			</div>
			<div class="col-6 col-lg-3">
				<label class="ahass-label" for="lapSampai">Sampai Tanggal</label>
				<input type="date" class="ahass-input" id="lapSampai">
			</div>
			<div class="col-12 col-lg-3">
				<button type="button" class="ahass-btn ahass-btn--primary" id="btnMuatLaporan">
					<i class="fa-solid fa-rotate"></i> Muat Laporan
				</button>
			</div>
			<div class="col-12 col-lg-3 text-lg-end">
				<span id="lblPeriode" style="font-size:13px;font-weight:700;color:var(--muted)">Memuat…</span>
			</div>
		</div>
		<div id="errPeriode" style="display:none;font-size:13px;color:var(--bad);font-weight:600;margin-top:10px"></div>
	</div>
</section>

<!-- ═══════════ HASIL LAPORAN ═══════════ -->
<section class="ahass-section">
	<div class="ahass-section__head">
		<span class="ahass-section__eyebrow">Hasil Rekap</span>
		<h2>Ringkasan Periode</h2>
	</div>

	<!-- status loading awal -->
	<div id="lapLoading" class="ahass-card">
		<div class="ahass-empty">
			<span class="ahass-empty__icon"><i class="fa-solid fa-spinner fa-spin"></i></span>
			<h4>Memuat laporan…</h4>
			<p>Mengambil agregasi data dari server.</p>
			<!-- Jaring pengaman: kalau JS versi lama masih ada di cache, user tidak menggantung -->
			<div id="lapStuck" style="display:none" class="mt-3">
				<p style="color:var(--bad);font-weight:700;max-width:420px">
					Memuat terlalu lama — kemungkinan versi JS lama masih tersimpan di browser.
				</p>
				<button type="button" class="ahass-btn ahass-btn--primary" onclick="location.reload()">
					<i class="fa-solid fa-rotate-right"></i> Muat Ulang Halaman
				</button>
			</div>
		</div>
	</div>

	<div id="hasilLaporan" class="d-none">
		<!-- Kartu ringkasan -->
		<div class="row g-4 mb-4" id="kartuLaporan">
			<?php
			$kartu = [
				['icon' => 'fa-calendar-day',   'label' => 'Total Booking',        'id' => 'repTotal'],
				['icon' => 'fa-circle-check',   'label' => 'Selesai',              'id' => 'repSelesai'],
				['icon' => 'fa-ban',            'label' => 'Dibatalkan',           'id' => 'repBatal'],
				['icon' => 'fa-money-bill-wave','label' => 'Estimasi Pendapatan',  'id' => 'repPendapatan'],
				['icon' => 'fa-chart-pie',      'label' => 'Okupansi Slot',        'id' => 'repOkupansi'],
				['icon' => 'fa-gauge-high',     'label' => 'Rata-rata / Hari',     'id' => 'repRata'],
			];
			foreach ($kartu as $k): ?>
			<div class="col-6 col-lg-4 col-xxl-2">
				<div class="ahass-card ahass-card--hover text-center">
					<span class="ahass-card__icon mx-auto mb-2"><i class="fa-solid <?= $k['icon'] ?>"></i></span>
					<div style="font-size:26px;font-weight:800;letter-spacing:-.03em;font-variant-numeric:tabular-nums" id="<?= $k['id'] ?>">—</div>
					<div style="font-size:12.5px;color:var(--muted);font-weight:600"><?= $k['label'] ?></div>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

		<div class="row g-4 mb-4">
			<!-- Chart breakdown per paket -->
			<div class="col-lg-5">
				<div class="ahass-card">
					<div class="ahass-card__head">
						<span class="ahass-card__icon"><i class="fa-solid fa-chart-column"></i></span>
						<div><h3>Booking per Paket</h3><small>Bar chart — dihitung server</small></div>
					</div>
					<div id="lapChartWrap" style="position:relative;height:280px">
						<canvas id="chartPaket"></canvas>
					</div>
					<div class="ahass-empty d-none" id="lapChartKosong">
						<span class="ahass-empty__icon"><i class="fa-solid fa-chart-column"></i></span>
						<h4>Belum ada data</h4>
						<p>Tidak ada booking pada rentang tanggal ini.</p>
					</div>
				</div>
			</div>

			<!-- Tabel breakdown per paket -->
			<div class="col-lg-7">
				<div class="ahass-card">
					<div class="ahass-card__head">
						<span class="ahass-card__icon"><i class="fa-solid fa-list-check"></i></span>
						<div><h3>Breakdown per Paket</h3><small>Jumlah booking & estimasi pendapatan</small></div>
					</div>
					<div class="table-responsive">
						<table class="ahass-table" id="tabelPaket">
							<thead>
								<tr>
									<th>Paket Servis</th>
									<th class="text-center">Harga</th>
									<th class="text-center">Booking</th>
									<th class="text-end">Estimasi</th>
								</tr>
							</thead>
							<tbody id="tbodyPaket"></tbody>
						</table>
					</div>
					<div class="ahass-empty d-none" id="lapKosong">
						<span class="ahass-empty__icon"><i class="fa-solid fa-inbox"></i></span>
						<h4>Belum ada booking pada rentang ini</h4>
						<p>Coba ganti rentang tanggal, atau kembalikan ke default 7 hari terakhir.</p>
						<button type="button" class="ahass-btn ahass-btn--primary mt-3" id="btnResetPeriode">
							<i class="fa-solid fa-clock-rotate-left"></i> Reset 7 Hari Terakhir
						</button>
					</div>
				</div>
			</div>
		</div>

		<!-- Aksi -->
		<div class="ahass-card">
			<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
				<small style="color:var(--muted);font-size:13px">
					<i class="fa-solid fa-circle-info"></i>
					Cetak hanya menampilkan laporan (elemen UI disembunyikan). CSV dibuka normal di Excel.
				</small>
				<div class="d-flex gap-2 flex-wrap">
					<button type="button" class="ahass-btn ahass-btn--primary" id="btnCetakLaporan">
						<i class="fa-solid fa-print"></i> Cetak Laporan
					</button>
					<button type="button" class="ahass-btn" style="background:#fff;border:1.5px solid var(--line);color:var(--ink-2)" id="btnCsvLaporan">
						<i class="fa-solid fa-file-csv"></i> Export CSV
					</button>
				</div>
			</div>
		</div>
	</div>
</section>

</main>

<script>
// Deteksi macet: bila setelah 8 detik hasil belum juga tampil, tampilkan bantuan.
setTimeout(function () {
	var loading = document.getElementById('lapLoading');
	var hasil   = document.getElementById('hasilLaporan');
	if (loading && hasil && !loading.classList.contains('d-none') && hasil.classList.contains('d-none')) {
		var stuck = document.getElementById('lapStuck');
		if (stuck) stuck.style.display = 'block';
	}
}, 8000);
</script>
