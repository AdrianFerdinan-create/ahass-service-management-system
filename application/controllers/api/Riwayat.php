<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Api/Riwayat — riwayat servis by nomor polisi (FR-12).
 *
 * GET /api/riwayat?plat=B 1234 ABC
 */
class Riwayat extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('booking_model');
	}

	public function index()
	{
		$plat = trim($this->input->get('plat'));

		if ($plat === '') {
			return $this->json_error('Nomor polisi belum diisi.', 422, 'VALIDASI');
		}

		$hasil = $this->booking_model->riwayat_by_plat($plat);

		return $this->json_ok($hasil);
	}
}
