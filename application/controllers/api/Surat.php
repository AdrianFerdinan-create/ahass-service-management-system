<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Api/Surat — Generator Surat Otomatis (FR-13).
 *
 * GET  /api/surat/template        → daftar template surat aktif
 * GET  /api/surat/template?id=    → detail template + daftar variabel (form dinamis)
 * POST /api/surat/generate        → render surat + nomor unik + simpan riwayat → 201
 * GET  /api/surat/riwayat         → daftar riwayat surat
 * GET  /api/surat/riwayat?id=     → detail body surat (preview ulang / cetak ulang)
 *
 * Semua respon JSON. Validasi variabel dijalankan di server (client bisa di-bypass),
 * nilai variabel dibersihkan dari tag HTML agar bebas XSS saat surat dirender.
 */
class Surat extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('surat_model');
	}

	// =====================================================================
	//  TEMPLATE — daftar & detail (form variabel dinamis)
	// =====================================================================
	public function template()
	{
		if ($this->input->method(TRUE) !== 'GET') {
			return $this->json_error('Method tidak diizinkan.', 405, 'METHOD_NOT_ALLOWED');
		}

		$id = $this->input->get('id');

		// Tanpa ?id= → daftar ringkas untuk dropdown
		if ($id === NULL || $id === '') {
			return $this->json_ok(['template' => $this->surat_model->daftar_template()]);
		}

		$t = $this->surat_model->template((int) $id);
		if (!$t) {
			return $this->json_error('Template surat tidak ditemukan.', 404, 'TIDAK_DITEMUKAN');
		}

		// body_template TIDAK dikirim — dirender di server (dan tetap aman)
		return $this->json_ok([
			'template' => [
				'id'         => (int) $t->id,
				'jenis'      => $t->jenis,
				'kode_jenis' => $t->kode_jenis,
				'deskripsi'  => $t->deskripsi,
				'variabel'   => $t->variabel_list,
			],
		]);
	}

	// =====================================================================
	//  GENERATE — render, nomor otomatis, simpan riwayat
	// =====================================================================
	public function generate()
	{
		if ($this->input->method(TRUE) !== 'POST') {
			return $this->json_error('Method tidak diizinkan.', 405, 'METHOD_NOT_ALLOWED');
		}

		// ---------- 1. Template ----------
		$template_id = (int) $this->input->post('template_id');
		if ($template_id <= 0) {
			return $this->json_error('Jenis surat belum dipilih.', 422, 'VALIDASI', [
				'errors' => ['template_id' => 'Pilih jenis surat terlebih dahulu.'],
			]);
		}

		$template = $this->surat_model->template($template_id);
		if (!$template) {
			return $this->json_error('Template surat tidak ditemukan.', 422, 'VALIDASI', [
				'errors' => ['template_id' => 'Jenis surat tidak tersedia.'],
			]);
		}

		// ---------- 2. Validasi tiap variabel (server-side) ----------
		$errors = [];
		$nilai  = [];

		foreach ($template->variabel_list as $v) {
			$nama  = isset($v['name']) ? $v['name'] : '';
			$label = isset($v['label']) ? $v['label'] : $nama;
			$wajib = !empty($v['required']);

			if ($nama === '') {
				continue;
			}

			// Bersihkan: trim + buang tag HTML (antisipasi XSS), batasi panjang
			$isi = trim(strip_tags((string) $this->input->post($nama)));

			if ($isi === '' && $wajib) {
				$errors[$nama] = $label . ' wajib diisi.';
				continue;
			}

			if (mb_strlen($isi) > Surat_model::MAKS_VARIABEL) {
				$errors[$nama] = $label . ' maksimal ' . Surat_model::MAKS_VARIABEL . ' karakter.';
				continue;
			}

			$nilai[$nama] = $isi;
		}

		if ($errors) {
			return $this->json_error(
				'Beberapa variabel surat belum diisi dengan benar.',
				422,
				'VALIDASI',
				['errors' => $errors]
			);
		}

		// ---------- 3. Tanggal surat (otomatis hari ini, boleh diubah) ----------
		$tanggal = trim((string) $this->input->post('tanggal_surat'));
		if ($tanggal === '') {
			$tanggal = date('Y-m-d');
		}

		$d = DateTime::createFromFormat('Y-m-d', $tanggal);
		if (!$d || $d->format('Y-m-d') !== $tanggal) {
			return $this->json_error('Format tanggal surat tidak valid.', 422, 'VALIDASI', [
				'errors' => ['tanggal_surat' => 'Format tanggal tidak valid (YYYY-MM-DD).'],
			]);
		}

		// ---------- 4. Generate ----------
		$hasil = $this->surat_model->generate($template, $nilai, $tanggal);

		if (!$hasil['ok']) {
			return $this->json_error($hasil['message'], 500, 'GAGAL');
		}

		$hasil['surat']['tanggal_label'] = $this->tanggal_label($tanggal);

		return $this->json_ok($hasil['surat'], 201);
	}

	// =====================================================================
	//  RIWAYAT — daftar & detail surat tersimpan
	// =====================================================================
	public function riwayat()
	{
		if ($this->input->method(TRUE) !== 'GET') {
			return $this->json_error('Method tidak diizinkan.', 405, 'METHOD_NOT_ALLOWED');
		}

		$id = $this->input->get('id');

		if ($id === NULL || $id === '') {
			return $this->json_ok(['riwayat' => $this->surat_model->daftar_riwayat()]);
		}

		$surat = $this->surat_model->riwayat((int) $id);
		if (!$surat) {
			return $this->json_error('Surat tidak ditemukan.', 404, 'TIDAK_DITEMUKAN');
		}

		$surat['tanggal_label'] = $this->tanggal_label(date('Y-m-d', strtotime($surat['created_at'])));

		return $this->json_ok($surat);
	}

	// =====================================================================
	//  Util
	// =====================================================================

	/**
	 * Tanggal surat versi Indonesia panjang: "4 Oktober 2026".
	 *
	 * @param  string $tanggal Y-m-d
	 * @return string
	 */
	protected function tanggal_label($tanggal)
	{
		$bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
			'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

		return sprintf(
			'%d %s %d',
			(int) date('j', strtotime($tanggal)),
			$bulan[(int) date('n', strtotime($tanggal))],
			(int) date('Y', strtotime($tanggal))
		);
	}
}
