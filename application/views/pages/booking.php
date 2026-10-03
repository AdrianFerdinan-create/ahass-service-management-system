<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<!-- ═══════════════ HERO (khusus halaman Booking) ═══════════════ -->
<section class="ahass-hero">
	<div class="container">
		<span class="ahass-hero__eyebrow"><span class="dot"></span> Slot Real-time · Astra Honda Authorized Service</span>

		<h1>Servis Motor Honda <em>Tanpa Antre</em><br>Kapan Pun, Di Mana Pun.</h1>

		<p class="ahass-hero__tag">
			Pilih tanggal, jam, dan paket servis — langsung dapat kode booking.
			Datang tepat waktu, motor kembali prima. <strong>Salam Satu HATI.</strong>
		</p>

		<div class="ahass-hero__actions">
			<a href="#booking" class="ahass-btn ahass-btn--light">
				<i class="fa-solid fa-calendar-plus"></i> Booking Sekarang
			</a>
			<a href="<?= base_url('daftar') ?>" class="ahass-btn ahass-btn--ghost">
				<i class="fa-solid fa-magnifying-glass"></i> Cek Daftar Booking
			</a>
		</div>

		<div class="ahass-hero__stats">
			<div class="ahass-hstat">
				<b id="statSlotKosong">—</b>
				<span>Slot kosong hari ini</span>
			</div>
			<div class="ahass-hstat">
				<b>3</b>
				<span>Kuota / jam</span>
			</div>
			<div class="ahass-hstat">
				<b>H+7</b>
				<span>Rentang booking</span>
			</div>
			<div class="ahass-hstat">
				<b id="statBookingTotal">—</b>
				<span>Total booking</span>
			</div>
		</div>
	</div>
</section>

<!-- ═══════════════ FORM BOOKING + SLOT ═══════════════ -->
<main class="container py-4 py-lg-5">
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

				<!-- ═══ FORM BOOKING (FR-01) ═══ -->
				<form id="formBooking" novalidate>
					<div class="row g-3">
						<div class="col-md-6">
							<label class="ahass-label" for="tanggal">Tanggal Servis <span class="req">*</span></label>
							<input type="date" class="ahass-input" id="tanggal" name="tanggal" required>
							<div class="ahass-help">Hari ini s/d H+7</div>
							<div class="ahass-err" data-err="tanggal"></div>
						</div>
						<div class="col-md-6">
							<label class="ahass-label" for="jam">Jam Servis <span class="req">*</span></label>
							<select class="ahass-input" id="jam" name="jam" required>
								<option value="">— pilih jam —</option>
							</select>
							<div class="ahass-help" id="jamHelp">Slot penuh otomatis tidak bisa dipilih</div>
							<div class="ahass-err" data-err="jam"></div>
						</div>
						<div class="col-md-6">
							<label class="ahass-label" for="plat_nomor">Nomor Polisi <span class="req">*</span></label>
							<input class="ahass-input" id="plat_nomor" name="plat_nomor"
								   placeholder="B 1234 ABC" maxlength="20" autocomplete="off" required>
							<div class="ahass-err" data-err="plat_nomor"></div>
						</div>
						<div class="col-md-6">
							<label class="ahass-label" for="nama_pelanggan">Nama Pelanggan <span class="req">*</span></label>
							<input class="ahass-input" id="nama_pelanggan" name="nama_pelanggan"
								   placeholder="Nama lengkap" maxlength="100" autocomplete="off" required>
							<div class="ahass-err" data-err="nama_pelanggan"></div>
						</div>
						<div class="col-md-6">
							<label class="ahass-label" for="tipe_motor">Tipe Motor <span class="req">*</span></label>
							<select class="ahass-input" id="tipe_motor" name="tipe_motor" required>
								<option value="">— pilih tipe —</option>
								<option>Beat</option><option>Scoopy</option><option>Genio</option>
								<option>Vario 125</option><option>Vario 160</option><option>PCX</option>
								<option>Filano</option><option>Supra X</option><option>Verza</option>
								<option>CB150R</option><option>CRF150L</option><option>Lainnya</option>
							</select>
							<div class="ahass-err" data-err="tipe_motor"></div>
						</div>
						<div class="col-md-6">
							<label class="ahass-label" for="paket_id">Paket Servis <span class="req">*</span></label>
							<select class="ahass-input" id="paket_id" name="paket_id" required>
								<option value="">— pilih paket —</option>
							</select>
							<div class="ahass-help" id="paketInfo"></div>
							<div class="ahass-err" data-err="paket_id"></div>
						</div>
						<div class="col-md-6">
							<label class="ahass-label" for="no_hp">No. HP <span class="text-muted-2">(opsional)</span></label>
							<input class="ahass-input" id="no_hp" name="no_hp" type="tel"
								   placeholder="081234567890" maxlength="20" autocomplete="off">
							<div class="ahass-err" data-err="no_hp"></div>
						</div>
						<div class="col-md-6">
							<label class="ahass-label" for="catatan">Catatan <span class="text-muted-2">(opsional)</span></label>
							<input class="ahass-input" id="catatan" name="catatan"
								   placeholder="Contoh: rem bunyi, CVT getar…" maxlength="500">
							<div class="ahass-err" data-err="catatan"></div>
						</div>

						<div class="col-12 mt-4">
							<button type="submit" class="ahass-btn ahass-btn--primary w-100 justify-content-center" id="btnBooking">
								<i class="fa-solid fa-calendar-check"></i> <span>Booking Sekarang</span>
							</button>
							<p class="ahass-help text-center mt-2 mb-0">
								<i class="fa-solid fa-shield-halved"></i> Data divalidasi di server · Kuota maks 3 kendaraan per jam
							</p>
						</div>
					</div>
				</form>
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

<!-- ═══════════════ AREA KONFIRMASI + MODAL STRUK (T2) ═══════════════ -->
<div id="konfirmasiBox" class="d-none"></div>

<!-- Modal Struk -->
<div class="modal fade" id="modalStruk" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content ahass-modal-box" style="border-radius:16px;overflow:hidden">
			<div class="modal-header" style="background:var(--ink);color:#fff;border:none;padding:14px 20px">
				<h5 class="modal-title" style="font-size:15px;font-weight:700">
					<i class="fa-solid fa-receipt" style="color:var(--red);margin-right:8px"></i>Struk Booking
				</h5>
				<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body" style="background:#F7F8FA;padding:22px">
				<div id="isiStruk"></div>
			</div>
			<div class="modal-footer" style="border:none;padding:14px 20px">
				<button type="button" class="ahass-btn" data-bs-dismiss="modal"
						style="background:#fff;border:1.5px solid var(--line);color:var(--ink-2)">Tutup</button>
				<button type="button" class="ahass-btn ahass-btn--primary" id="btnCetakStruk">
					<i class="fa-solid fa-print"></i> Cetak Struk
				</button>
			</div>
		</div>
	</div>
</div>
</main>
