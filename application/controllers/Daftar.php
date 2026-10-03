<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Daftar — halaman /daftar (tabel booking + filter + aksi).
 * Data dimuat via AJAX ke Api/Booking::list (tanpa reload).
 */
class Daftar extends CI_Controller {

	public function index()
	{
		$data = [
			'title' => 'Daftar Booking — AHASS',
			'menu'  => 'daftar',
		];

		$this->load->view('layout/header', $data);
		$this->load->view('pages/daftar', $data);
		$this->load->view('layout/footer', $data);
	}
}
