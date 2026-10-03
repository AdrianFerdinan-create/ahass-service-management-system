<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php $this->load->view('partials/page-head', [
	'menu'       => '',
	'eyebrow'    => 'Error 404',
	'judul'      => 'Halaman Tidak Ditemukan',
	'deskripsi'  => 'Halaman yang Anda minta tidak tersedia atau sudah dipindahkan.',
	'breadcrumb' => ['404'],
]); ?>

<main class="container py-4 py-lg-5">
<section class="ahass-section">
	<div class="ahass-card">
		<div class="ahass-empty">
			<span class="ahass-empty__icon"><i class="fa-solid fa-compass-minus"></i></span>
			<h4>404 — Alamat tidak ditemukan</h4>
			<p>Coba pilih menu di atas, atau kembali ke halaman booking untuk mendaftar servis.</p>
			<div class="d-flex gap-2 justify-content-center mt-3 flex-wrap">
				<a class="ahass-btn ahass-btn--primary" href="<?= base_url() ?>">
					<i class="fa-solid fa-calendar-check"></i> Ke Halaman Booking
				</a>
				<a class="ahass-btn" style="background:#fff;border:1.5px solid var(--line);color:var(--ink-2)"
				   href="<?= base_url('daftar') ?>">
					<i class="fa-solid fa-list-ul"></i> Daftar Booking
				</a>
			</div>
		</div>
	</div>
</section>
</main>
