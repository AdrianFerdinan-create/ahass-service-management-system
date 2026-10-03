<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
/**
 * Page header compact — dipakai halaman /daftar /dashboard /laporan /surat.
 * Variabel: $judul, $deskripsi, $eyebrow (opsional), $breadcrumb (opsional)
 */
$eyebrow   = isset($eyebrow) ? $eyebrow : 'AHASS Booking';
$breadcrumb = isset($breadcrumb) ? $breadcrumb : [];
?>
<section class="ahass-hero ahass-hero--page">
	<div class="container">
		<?php if ($breadcrumb): ?>
		<nav class="ahass-breadcrumb" aria-label="Breadcrumb">
			<a href="<?= base_url() ?>"><i class="fa-solid fa-house"></i> Beranda</a>
			<?php foreach ($breadcrumb as $b): ?>
				<span class="sep">/</span><span><?= htmlspecialchars($b, ENT_QUOTES, 'UTF-8') ?></span>
			<?php endforeach; ?>
		</nav>
		<?php endif; ?>

		<span class="ahass-hero__eyebrow"><span class="dot"></span> <?= htmlspecialchars($eyebrow, ENT_QUOTES, 'UTF-8') ?></span>
		<h1><?= htmlspecialchars($judul, ENT_QUOTES, 'UTF-8') ?></h1>
		<p class="ahass-hero__tag"><?= htmlspecialchars($deskripsi, ENT_QUOTES, 'UTF-8') ?></p>
	</div>
</section>
