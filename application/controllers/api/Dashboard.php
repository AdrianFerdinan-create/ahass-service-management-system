<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Api/Dashboard — statistik ringkas (FR-05).
 *
 * GET /api/dashboard/stats → kartu statistik + data grafik 7 hari
 */
class Dashboard extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('booking_model');
	}

	public function stats()
	{
		return $this->json_ok([
			'kartu'   => $this->booking_model->statistik_dashboard(),
			'grafik'  => $this->booking_model->grafik_7_hari(),
		]);
	}
}
