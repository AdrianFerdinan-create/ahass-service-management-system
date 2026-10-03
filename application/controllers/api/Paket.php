<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Api/Paket — daftar paket servis (FR-06).
 *
 * GET /api/paket
 */
class Paket extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('paket_model');
	}

	public function index()
	{
		return $this->json_ok([
			'paket' => $this->paket_model->semua_aktif(),
		]);
	}
}
