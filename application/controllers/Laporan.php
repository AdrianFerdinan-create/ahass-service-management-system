<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Laporan — halaman /laporan (rekap + cetak + CSV).
 * Data dimuat via AJAX ke Api/Report.
 */
class Laporan extends CI_Controller {

	public function index()
	{
		$data = [
			'title' => 'Laporan — AHASS',
			'menu'  => 'laporan',
		];

		$this->load->view('layout/header', $data);
		$this->load->view('pages/laporan', $data);
		$this->load->view('layout/footer', $data);
	}
}
