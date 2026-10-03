<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dashboard — halaman /dashboard (statistik, grafik, heatmap, riwayat).
 * Data dimuat via AJAX ke Api/Dashboard + Api/Slot::heatmap + Api/Riwayat.
 */
class Dashboard extends CI_Controller {

	public function index()
	{
		$data = [
			'title' => 'Dashboard — AHASS',
			'menu'  => 'dashboard',
		];

		$this->load->view('layout/header', $data);
		$this->load->view('pages/dashboard', $data);
		$this->load->view('layout/footer', $data);
	}
}
