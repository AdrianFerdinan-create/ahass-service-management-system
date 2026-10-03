<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php $this->load->view('partials/page-head', [
	'menu'       => 'daftar',
	'eyebrow'    => 'Pantau Semua Booking',
	'judul'      => 'Daftar Booking',
	'deskripsi'  => 'Filter tanggal, cari plat/nama/kode booking, ubah status, atau batalkan — semua aksi tanpa reload.',
	'breadcrumb' => ['Daftar Booking'],
]); ?>

<main class="container py-4 py-lg-5">
<section class="ahass-section">
	<div class="ahass-section__head">
		<span class="ahass-section__eyebrow">Tabel Terfilter</span>
		<h2>Semua Booking Masuk</h2>
		<p>Pagination server-side 10 baris per halaman, filter gabungan (tanggal + pencarian + paket + status).</p>
	</div>

	<div class="ahass-card">
		<!-- ═══ FILTER BAR (FR-04) ═══ -->
		<div id="filterBar" class="row g-3 mb-4">
			<div class="col-6 col-lg-3">
				<label class="ahass-label" for="fTanggal">Tanggal</label>
				<input type="date" class="ahass-input" id="fTanggal">
			</div>
			<div class="col-6 col-lg-3">
				<label class="ahass-label" for="fStatus">Status</label>
				<select class="ahass-input" id="fStatus">
					<option value="">Semua status</option>
					<option value="pending">Pending</option>
					<option value="dikonfirmasi">Dikonfirmasi</option>
					<option value="selesai">Selesai</option>
					<option value="dibatalkan">Dibatalkan</option>
				</select>
			</div>
			<div class="col-6 col-lg-3">
				<label class="ahass-label" for="fPaket">Paket</label>
				<select class="ahass-input" id="fPaket">
					<option value="">Semua paket</option>
				</select>
			</div>
			<div class="col-6 col-lg-3">
				<label class="ahass-label" for="fCari">Pencarian</label>
				<div style="position:relative">
					<input class="ahass-input" id="fCari" placeholder="Plat / nama / kode booking…"
						   style="padding-left:36px" autocomplete="off">
					<i class="fa-solid fa-magnifying-glass" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#A6ACB7;font-size:13px"></i>
				</div>
			</div>
			<div class="col-12 d-flex flex-wrap gap-2 align-items-center">
				<button type="button" class="ahass-btn ahass-btn--primary" id="btnFilter">
					<i class="fa-solid fa-magnifying-glass"></i> Terapkan Filter
				</button>
				<button type="button" class="ahass-btn" id="btnResetFilter"
						style="background:#fff;border:1.5px solid var(--line);color:var(--ink-2)">
					<i class="fa-solid fa-rotate-left"></i> Reset
				</button>
				<span class="ms-auto" id="infoTotal" style="font-size:13px;color:var(--muted);font-weight:600">
					Memuat…
				</span>
			</div>
		</div>

		<!-- ═══ TABEL BOOKING (FR-04) ═══ -->
		<div class="table-responsive">
			<table class="ahass-table" id="tabelBooking">
				<thead>
					<tr>
						<th>#</th>
						<th>Kode Booking</th>
						<th>Tanggal</th>
						<th>Jam</th>
						<th>Plat</th>
						<th>Nama</th>
						<th>Motor</th>
						<th>Paket</th>
						<th>Harga</th>
						<th>Status</th>
						<th class="text-end">Aksi</th>
					</tr>
				</thead>
				<tbody id="tbodyBooking">
					<!-- diisi via AJAX -->
				</tbody>
			</table>
		</div>

		<!-- Empty state -->
		<div id="emptyState" class="ahass-empty d-none">
			<span class="ahass-empty__icon"><i class="fa-solid fa-inbox"></i></span>
			<h4>Belum ada booking yang cocok</h4>
			<p>Coba ubah filter atau kata kunci pencarianmu.</p>
			<button type="button" class="ahass-btn ahass-btn--primary mt-2" id="btnResetDariEmpty">
				<i class="fa-solid fa-rotate-left"></i> Reset Filter
			</button>
		</div>

		<!-- Pagination -->
		<nav class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2"
			 id="navPagination">
			<span style="font-size:13px;color:var(--muted)" id="infoHalaman">—</span>
			<ul class="pagination ahass-pagination mb-0" id="pagination"></ul>
		</nav>
	</div>
</section>

<!-- ═══ MODAL DETAIL BOOKING (FR-08) ═══ -->
<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
		<div class="modal-content ahass-modal-box" style="border-radius:16px;overflow:hidden">
			<div class="modal-header" style="background:var(--ink);color:#fff;border:none;padding:14px 20px">
				<h5 class="modal-title" style="font-size:15px;font-weight:700">
					<i class="fa-solid fa-circle-info" style="color:var(--red);margin-right:8px"></i>Detail Booking
				</h5>
				<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body" style="background:#F7F8FA" id="isiDetail">
				<div class="ahass-empty"><span class="ahass-empty__icon"><i class="fa-solid fa-spinner fa-spin"></i></span><h4>Memuat…</h4></div>
			</div>
			<div class="modal-footer" style="border:none;padding:14px 20px;flex-wrap:wrap;gap:8px">
				<button type="button" class="ahass-btn" data-bs-dismiss="modal"
						style="background:#fff;border:1.5px solid var(--line);color:var(--ink-2)">Tutup</button>
				<button type="button" class="ahass-btn ahass-btn--primary" id="btnCetakDariDetail">
					<i class="fa-solid fa-print"></i> Struk
				</button>
				<button type="button" class="ahass-btn" id="btnBatalkanDariDetail"
						style="background:var(--bad);color:#fff">Batal</button>
			</div>
		</div>
	</div>
</div>
</main>
