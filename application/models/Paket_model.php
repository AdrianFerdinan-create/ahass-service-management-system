<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Paket_model — data paket servis (FR-06).
 */
class Paket_model extends CI_Model {

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Semua paket aktif (untuk dropdown form & API publik).
	 *
	 * @return array
	 */
	public function semua_aktif()
	{
		return $this->db
			->where('aktif', 1)
			->order_by('id', 'ASC')
			->get('paket_servis')
			->result_array();
	}

	/**
	 * Ambil 1 paket berdasarkan id (NULL jika tidak ada / non-aktif).
	 *
	 * @param  int $id
	 * @return object|null
	 */
	public function ambil($id)
	{
		$paket = $this->db
			->where('id', (int) $id)
			->where('aktif', 1)
			->get('paket_servis')
			->row();

		return $paket ?: NULL;
	}
}
