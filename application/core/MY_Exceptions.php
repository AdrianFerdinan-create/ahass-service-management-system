<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Exceptions — semua error pada endpoint /api/* dibalas JSON (PRD §7).
 *
 * Sebelumnya kegagalan CSRF (POST tanpa token) memunculkan halaman error
 * HTML bawaan CodeIgniter. Karena seluruh aksi aplikasi ini lewat AJAX,
 * respon harus tetap JSON agar penanganan error di app.js konsisten:
 *   { success:false, code:..., message:... } + status HTTP yang benar.
 *
 * Halaman biasa (di luar /api) TIDAK terpengaruh — tetap memakai
 * perilaku bawaan CI_Exceptions.
 */
class MY_Exceptions extends CI_Exceptions {

	/**
	 * Tangkap seluruh show_error() CodeIgniter (CSRF gagal, error DB,
	 * file tak ditemukan, dst.) — untuk API kirim JSON, untuk halaman
	 * biasa lempar ke parent.
	 *
	 * @param  string       $heading
	 * @param  string|array $message
	 * @param  string       $template
	 * @param  int          $status_code
	 * @return string
	 */
	public function show_error($heading, $message, $template = 'error_general', $status_code = 500)
	{
		if (is_cli() || ! $this->permintaan_api()) {
			return parent::show_error($heading, $message, $template, $status_code);
		}

		// Detail error (mungkin memuat SQL/traceback) hanya dicatat ke log,
		// TIDAK dikirim ke user — sesuai PRD §10 (tanpa traceback).
		log_message('error', sprintf(
			'API error %d: %s',
			$status_code,
			is_array($message) ? implode(' | ', $message) : (string) $message
		));

		set_status_header($status_code);

		return (string) json_encode([
			'success' => FALSE,
			'code'    => $this->kode_error($status_code),
			'message' => $this->pesan_error($status_code),
			'status'  => (int) $status_code,
		], JSON_UNESCAPED_UNICODE);
	}

	/**
	 * Apakah request ini untuk endpoint API? Dicek langsung dari REQUEST_URI
	 * (bukan uri_string) agar tetap andal meski belum saat routing selesai.
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

	/**
	 * Kode mesin untuk payload JSON.
	 *
	 * @param  int $status
	 * @return string
	 */
	protected function kode_error($status)
	{
		$peta = [
			403 => 'CSRF_TIDAK_VALID',
			404 => 'ENDPOINT_TIDAK_DITEMUKAN',
			405 => 'METHOD_NOT_ALLOWED',
			503 => 'LAYANAN_TIDAK_TERSEDIA',
		];

		return isset($peta[$status]) ? $peta[$status] : 'SERVER_ERROR';
	}

	/**
	 * Pesan ramah Bahasa Indonesia (tanpa detail teknis).
	 *
	 * @param  int $status
	 * @return string
	 */
	protected function pesan_error($status)
	{
		$peta = [
			403 => 'Token keamanan (CSRF) tidak valid atau kedaluwarsa. Muat ulang halaman lalu coba lagi.',
			404 => 'Endpoint tidak ditemukan.',
			405 => 'Method tidak diizinkan.',
			503 => 'Layanan sedang tidak tersedia. Silakan coba lagi.',
		];

		if (isset($peta[$status])) {
			return $peta[$status];
		}

		return $status >= 500
			? 'Terjadi kesalahan pada server. Silakan coba lagi.'
			: 'Permintaan tidak dapat diproses. Silakan periksa kembali data Anda.';
	}
}
