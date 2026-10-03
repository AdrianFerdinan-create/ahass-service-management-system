<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Navigasi multi-page — tiap menu = halaman tersendiri (PRD §6/§7)
$menu_aktif = isset($menu) ? $menu : 'booking';
$nav = [
	['key' => 'booking',  'url' => base_url(),               'icon' => 'fa-calendar-check',      'label' => 'Booking'],
	['key' => 'daftar',   'url' => base_url('daftar'),       'icon' => 'fa-list-ul',             'label' => 'Daftar Booking'],
	['key' => 'dashboard','url' => base_url('dashboard'),    'icon' => 'fa-chart-pie',           'label' => 'Dashboard'],
	['key' => 'laporan',  'url' => base_url('laporan'),      'icon' => 'fa-file-lines',          'label' => 'Laporan'],
	['key' => 'surat',    'url' => base_url('surat'),        'icon' => 'fa-envelope-open-text',  'label' => 'Surat'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
	<meta name="description" content="Sistem Booking Servis Online AHASS — daftar servis motor Honda tanpa antre, pilih tanggal, jam, dan paket servis.">
	<meta property="og:title" content="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>">
	<meta property="og:description" content="Booking servis motor Honda online — cepat, mudah, tanpa antre.">
	<meta property="og:type" content="website">
	<meta name="theme-color" content="#E4002B">

	<!-- Favicon (H merah) -->
	<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='%23E4002B'/><text x='50' y='73' font-size='64' font-family='Arial' font-weight='bold' fill='white' text-anchor='middle'>H</text></svg>">

	<!-- Fonts -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

	<!-- Bootstrap 5 + FontAwesome -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

	<!-- Theme AHASS -->
	<link href="<?= base_url('assets/css/app.css') ?>?v=5" rel="stylesheet"><!-- v5: T6 — slot 2 kolom + sticky kolom Plat di mobile -->
</head>
<body>

<!-- ═══════════════ HEADER (sticky glass) ═══════════════ -->
<header class="ahass-header">
	<nav class="navbar navbar-expand-lg">
		<div class="container">
			<a class="navbar-brand ahass-logo" href="<?= base_url() ?>">
				<span class="ahass-logo__mark">H</span>
				<span class="ahass-logo__text">AHASS<small>Booking Servis Online</small></span>
			</a>
			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain"
					aria-label="Menu">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse" id="navMain">
				<ul class="navbar-nav ms-auto align-items-lg-center">
					<?php foreach ($nav as $n): ?>
					<li class="nav-item">
						<a class="nav-link<?= $menu_aktif === $n['key'] ? ' active' : '' ?>" href="<?= $n['url'] ?>">
							<i class="fa-solid <?= $n['icon'] ?>"></i><?= $n['label'] ?>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</nav>
</header>
