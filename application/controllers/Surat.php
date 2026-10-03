<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Surat — halaman /surat (FR-13 generator surat otomatis).
 * Data dimuat via AJAX ke Api/Surat.
 */
class Surat extends CI_Controller {

	public function index()
	{
		$data = [
			'title' => 'Generator Surat — AHASS',
			'menu'  => 'surat',
		];

		$this->load->view('layout/header', $data);
		$this->load->view('pages/surat', $data);
		$this->load->view('layout/footer', $data);
	}
}
