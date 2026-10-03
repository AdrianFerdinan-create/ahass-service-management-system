<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!-- Area cetak global (hanya tampil di @media print) -->
<div id="printArea"></div>

<!-- ================= FOOTER ================= -->
<footer class="ahass-footer">
	<div class="container d-flex flex-column flex-md-row align-items-center justify-content-between">
		<div class="ahass-footer__brand">
			<span class="ahass-logo__mark">H</span>
			<span>&copy; 2026 AHASS — Astra Honda Authorized Service Station</span>
		</div>
		<div class="ahass-footer__tag">Salam Satu HATI 🤝</div>
	</div>
</footer>

<!-- JS libs -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- CSRF token untuk semua request AJAX -->
<script>
window.AHASS = {
	baseUrl: '<?= base_url() ?>',
	csrfName: '<?= $this->security->get_csrf_token_name() ?>',
	csrfHash: '<?= $this->security->get_csrf_hash() ?>'
};
</script>

<!-- Aplikasi -->
<script src="<?= base_url('assets/js/app.js') ?>?v=3"></script><!-- v3: FR-09 laporan (T5) + FR-13 surat (T5.5) — WAJIB naikkan versi tiap ubah JS/CSS agar browser tidak pakai cache lama -->
</body>
</html>
