<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Welcome — memuat halaman utama SPA Sistem Booking Servis AHASS.
 *
 * Semua interaksi (booking, filter, dashboard, laporan, surat)
 * dilakukan via AJAX ke controller Api/* — halaman tidak pernah reload.
 */
class Welcome extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Halaman Booking (utama): hero + form + slot live + konfirmasi
	 */
	public function index()
	{
		$data = [
			'title' => 'Booking Servis — AHASS',
			'menu'  => 'booking',
		];

		$this->load->view('layout/header', $data);
		$this->load->view('pages/booking', $data);
		$this->load->view('layout/footer', $data);
	}
}
