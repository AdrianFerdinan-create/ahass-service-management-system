<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Notfound — penanganan 404 (route 404_override, PRD §6).
 *
 * - Endpoint /api/*  → 404 JSON rapi:
 *     { success:false, code:"ENDPOINT_TIDAK_DITEMUKAN", message:"Endpoint tidak ditemukan." }
 * - Halaman biasa    → halaman 404 bergaya AHASS (bukan JSON mentah),
 *                     agar penguji yang salah mengetik URL tetap melihat
 *                     halaman rapi dengan navigasi kembali.
 *
 * Catatan: render dilakukan lewat view sendiri (bukan show_404()) untuk
 * menghindari rekursi ke 404_override ini.
 */
class Notfound extends MY_Controller {

	public function index()
	{
		// ---------- Endpoint API → JSON ----------
		if ($this->permintaan_api()) {
			return $this->json_error('Endpoint tidak ditemukan.', 404, 'ENDPOINT_TIDAK_DITEMUKAN');
		}

		// ---------- Halaman biasa → HTML 404 ----------
		$this->output->set_status_header(404);

		$data = [
			'title' => 'Halaman Tidak Ditemukan — AHASS',
			'menu'  => '',
		];

		$this->load->view('layout/header', $data);
		$this->load->view('pages/404', $data);
		$this->load->view('layout/footer', $data);
	}
}
