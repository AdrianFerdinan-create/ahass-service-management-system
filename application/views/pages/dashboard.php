<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php $this->load->view('partials/page-head', [
	'menu'       => 'dashboard',
	'eyebrow'    => 'Ringkasan Operasional',
	'judul'      => 'Dashboard AHASS',
	'deskripsi'  => 'Statistik hari ini, grafik 7 hari terakhir, heatmap okupansi slot, dan riwayat servis by nomor polisi.',
	'breadcrumb' => ['Dashboard'],
]); ?>

<main class="container py-4 py-lg-5">
<section class="ahass-section">
	<div class="ahass-section__head">
		<span class="ahass-section__eyebrow">Statistik Real-time</span>
		<h2>Kartu Ringkasan</h2>
		<p>Booking hari ini, slot terisi, estimasi pendapatan, dan booking batal — dihitung server-side.</p>
	</div>

	<div class="row g-4" id="kartuStatistik">
		<?php
		// Kartu placeholder (isi datanya di T4 via /api/dashboard/stats)
		$kartu = [
			['icon' => 'fa-calendar-check', 'label' => 'Booking Hari Ini', 'id' => 'statBookingHariIni'],
			['icon' => 'fa-layer-group',    'label' => 'Slot Terisi',      'id' => 'statSlotTerisi'],
			['icon' => 'fa-money-bill-wave','label' => 'Estimasi Pendapatan', 'id' => 'statPendapatan'],
			['icon' => 'fa-ban',            'label' => 'Booking Dibatalkan', 'id' => 'statBatal'],
		];
		foreach ($kartu as $k): ?>
		<div class="col-6 col-lg-3">
			<div class="ahass-card ahass-card--hover text-center">
				<span class="ahass-card__icon mx-auto mb-2"><i class="fa-solid <?= $k['icon'] ?>"></i></span>
				<div style="font-size:28px;font-weight:800;letter-spacing:-.03em;font-variant-numeric:tabular-nums" id="<?= $k['id'] ?>">—</div>
				<div style="font-size:12.5px;color:var(--muted);font-weight:600"><?= $k['label'] ?></div>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
</section>

<section class="ahass-section">
	<div class="ahass-section__head">
		<span class="ahass-section__eyebrow">Tren &amp; Okupansi</span>
		<h2>Grafik &amp; Heatmap</h2>
		<p>Grafik booking 7 hari (Chart.js) dan heatmap 7 hari × 9 jam dengan warna sesuai kuota.</p>
	</div>

	<div class="row g-4">
		<div class="col-lg-7">
			<div class="ahass-card">
				<div class="ahass-card__head">
					<span class="ahass-card__icon"><i class="fa-solid fa-chart-line"></i></span>
					<div><h3>Booking 7 Hari Terakhir</h3><small>Chart.js</small></div>
				</div>
				<div id="grafikWrap">
					<div style="position:relative;height:290px">
						<canvas id="grafikBooking"></canvas>
					</div>
					<div class="ahass-empty d-none" id="grafikKosong">
						<span class="ahass-empty__icon"><i class="fa-solid fa-chart-column"></i></span>
						<h4>Belum ada data grafik</h4>
						<p>Data 7 hari terakhir akan tampil di sini.</p>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-5">
			<div class="ahass-card">
				<div class="ahass-card__head">
					<span class="ahass-card__icon"><i class="fa-solid fa-table-cells"></i></span>
					<div><h3>Heatmap Okupansi</h3><small>7 hari × 9 jam</small></div>
				</div>
				<div id="heatmapWrap">
					<div id="heatmapGrid" class="ahass-heatmap"></div>
					<div class="ahass-heat-legend">
						<span><i style="background:#DCFCE7"></i> 0/3</span>
						<span><i style="background:#86EFAC"></i> 1/3</span>
						<span><i style="background:#FCD34D"></i> 2/3</span>
						<span><i style="background:#F87171"></i> PENUH</span>
						<span><i style="background:#E5E7EB"></i> Lewat</span>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="ahass-section">
	<div class="ahass-section__head">
		<span class="ahass-section__eyebrow">Riwayat Pelanggan</span>
		<h2>Cari Riwayat by Plat</h2>
		<p>Masukkan nomor polisi untuk melihat seluruh riwayat servis pelanggan tersebut.</p>
	</div>

	<div class="ahass-card">
		<div class="d-flex gap-2 flex-wrap mb-4">
			<input class="ahass-input" id="inputPlatRiwayat" placeholder="Contoh: B 1199 PSG"
				   style="max-width:320px;text-transform:uppercase" autocomplete="off">
			<button type="button" class="ahass-btn ahass-btn--primary" id="btnCariRiwayat">
				<i class="fa-solid fa-magnifying-glass"></i> Cari Riwayat
			</button>
		</div>

		<div id="hasilRiwayat">
			<div class="ahass-empty">
				<span class="ahass-empty__icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
				<h4>Masukkan nomor polisi</h4>
				<p>Riwayat servis pelanggan akan muncul di sini — coba <code>B 1199 PSG</code> (2 kunjungan).</p>
			</div>
		</div>
	</div>
</section>

<!-- ═══ MODAL: daftar booking pada sel heatmap ═══ -->
<div class="modal fade" id="modalSlot" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
		<div class="modal-content ahass-modal-box" style="border-radius:16px;overflow:hidden">
			<div class="modal-header" style="background:var(--ink);color:#fff;border:none;padding:14px 20px">
				<h5 class="modal-title" style="font-size:15px;font-weight:700" id="judulModalSlot">
					<i class="fa-solid fa-clock" style="color:var(--red);margin-right:8px"></i>Booking pada Slot
				</h5>
				<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body" style="background:#F7F8FA" id="isiModalSlot"></div>
		</div>
	</div>
</div>
</main>
