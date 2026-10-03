<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Controller — dasar untuk semua controller API AHASS.
 * Menjamin SEMUA respon API berformat JSON + header yang benar (§7 PRD).
 */
class MY_Controller extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Respon JSON sukses.
	 *
	 * @param array   $data
	 * @param integer $status  HTTP status (200/201)
	 */
	protected function json_ok($data = [], $status = 200)
	{
		$this->output
			->set_status_header($status)
			->set_content_type('application/json', 'utf-8')
			->set_output(json_encode([
				'success' => TRUE,
				'data'    => $data,
			], JSON_UNESCAPED_UNICODE));
	}

	/**
	 * Respon JSON gagal.
	 *
	 * @param string  $message  Pesan human-friendly (Bahasa Indonesia)
	 * @param integer $status   HTTP status (404/409/422/500)
	 * @param string  $code     Kode mesin (SLOT_PENUH, VALIDASI, dst.)
	 * @param array   $extra    Field tambahan (errors, alternatives, data)
	 */
	protected function json_error($message, $status = 400, $code = 'ERROR', $extra = [])
	{
		$payload = array_merge([
			'success' => FALSE,
			'code'    => $code,
			'message' => $message,
		], $extra);

		$this->output
			->set_status_header($status)
			->set_content_type('application/json', 'utf-8')
			->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE));
	}

	/**
	 * Apakah request ini ditujukan ke endpoint /api/* ?
	 * Dari REQUEST_URI agar andal tanpa bergantung pada state routing.
	 *
	 * (Catatan: MY_Exceptions punya salinan metode ini — kelas itu dievaluasi
	 * terlalu awal saat bootstrap, sebelum helper/controller tersedia.)
	 *
	 * @return bool
	 */
	protected function permintaan_api()
	{
		$path = isset($_SERVER['REQUEST_URI'])
			? (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
			: '';

		return (bool) preg_match('#/api(/|$)#i', $path);
	}
}
