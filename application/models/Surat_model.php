<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Surat_model — template & riwayat surat otomatis (FR-13).
 *
 * Menyimpan template surat dengan placeholder {{variabel}},
 * merender isi surat dari input user, membuat nomor surat unik
 * format AHASS/[KODE-JENIS]/[ROMAWI-BULAN]/[TAHUN],
 * lalu mengarsipkannya ke tabel riwayat_surat agar bisa
 * dipreview ulang dan dicetak ulang kapan pun.
 */
class Surat_model extends CI_Model {

	/** Batas panjang tiap nilai variabel (keamanan input). */
	const MAKS_VARIABEL = 500;

	/** Batas jumlah riwayat yang dikembalikan dalam satu daftar. */
	const MAKS_RIWAYAT = 100;

	public function __construct()
	{
		parent::__construct();
	}

	// =====================================================================
	//  TEMPLATE
	// =====================================================================

	/**
	 * Semua template surat aktif (untuk dropdown pilihan).
	 *
	 * @return array [{id, jenis, kode_jenis, deskripsi}]
	 */
	public function daftar_template()
	{
		return $this->db
			->select('id, jenis, kode_jenis, deskripsi', FALSE)
			->where('aktif', 1)
			->order_by('id', 'ASC')
			->get('template_surat')
			->result_array();
	}

	/**
	 * Satu template aktif + daftar variabel ter-decode.
	 * Mengembalikan NULL bila tidak ditemukan / non-aktif.
	 *
	 * @param  int $id
	 * @return object|null
	 */
	public function template($id)
	{
		$t = $this->db
			->where('id', (int) $id)
			->where('aktif', 1)
			->get('template_surat')
			->row();

		if (!$t) {
			return NULL;
		}

		$t->variabel_list = json_decode($t->variabel_list, TRUE) ?: [];

		return $t;
	}

	// =====================================================================
	//  GENERATE
	// =====================================================================

	/**
	 * Render template dengan nilai variabel, buat nomor surat unik,
	 * lalu simpan ke riwayat_surat.
	 *
	 * @param  object $template  Row template_surat (variabel_list sudah decode)
	 * @param  array  $nilai     {nama_variabel => nilai bersih}
	 * @param  string $tanggal   Y-m-d tanggal surat
	 * @return array  ['ok'=>bool, 'surat'=>array] | ['ok'=>false, 'message'=>..]
	 */
	public function generate($template, $nilai, $tanggal)
	{
		// Ganti seluruh placeholder {{kunci}} — placeholder yang tidak diisi
		// dibiarkan apa adanya agar tidak menghasilkan surat kosong diam-diam.
		$body = $template->body_template;
		foreach ($nilai as $kunci => $isi) {
			$body = str_replace('{{' . $kunci . '}}', $isi, $body);
		}

		$nomor = $this->nomor_surat($template->kode_jenis);
		if ($nomor === NULL) {
			return [
				'ok'      => FALSE,
				'message' => 'Gagal membuat nomor surat unik. Silakan coba lagi.',
			];
		}

		$baris = [
			'nomor_surat'   => $nomor,
			'template_id'   => (int) $template->id,
			'data_variabel' => json_encode($nilai, JSON_UNESCAPED_UNICODE),
			'body_final'    => $body,
			'created_at'    => date('Y-m-d H:i:s'),
		];

		// Simpan — bila tabrakan nomor (dua generate bersamaan), coba ulang
		// sampai 3 kali dengan nomor kandidat berikutnya.
		// db_debug dimatikan sementara: kegagalan insert harus kembali sebagai
		// JSON error, bukan halaman error database (terutama di mode development).
		$db_debug_lama    = $this->db->db_debug;
		$this->db->db_debug = FALSE;

		$tersimpan = FALSE;
		for ($coba = 0; $coba < 3 && !$tersimpan; $coba++) {
			if ($coba > 0) {
				$baris['nomor_surat'] = $nomor . '-' . ($coba + 1);
			}

			$this->db->insert('riwayat_surat', $baris);
			$tersimpan = $this->db->affected_rows() > 0;
		}

		$this->db->db_debug = $db_debug_lama;

		if (!$tersimpan) {
			return [
				'ok'      => FALSE,
				'message' => 'Gagal menyimpan surat. Silakan coba lagi.',
			];
		}

		$id = (int) $this->db->insert_id();

		return [
			'ok'   => TRUE,
			'surat' => [
				'id'           => $id,
				'nomor_surat'  => $baris['nomor_surat'],
				'tanggal'      => $tanggal,
				'template_id'  => (int) $template->id,
				'jenis'        => $template->jenis,
				'kode_jenis'   => $template->kode_jenis,
				'variabel'     => $nilai,
				'body_final'   => $body,
				'body_html'    => $this->render_html($body),
				'created_at'   => $baris['created_at'],
			],
		];
	}

	/**
	 * Nomor surat unik: AHASS/[KODE]/[ROMAWI]/[TAHUN].
	 * Bila sudah terpakai (bulan yang sama), diberi akhiran -2, -3, dst.
	 *
	 * @param  string $kode_jenis
	 * @return string|null
	 */
	protected function nomor_surat($kode_jenis)
	{
		$romawi = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI',
			'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

		$dasar = sprintf(
			'AHASS/%s/%s/%s',
			strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $kode_jenis)),
			$romawi[(int) date('m')],
			date('Y')
		);

		if (!$this->db->where('nomor_surat', $dasar)->count_all_results('riwayat_surat')) {
			return $dasar;
		}

		for ($n = 2; $n <= 999; $n++) {
			$kandidat = $dasar . '-' . $n;
			if (!$this->db->where('nomor_surat', $kandidat)->count_all_results('riwayat_surat')) {
				return $kandidat;
			}
		}

		return NULL; // sangat kecil kemungkinannya
	}

	// =====================================================================
	//  RIWAYAT
	// =====================================================================

	/**
	 * Daftar surat yang pernah dibuat (terbaru dulu) + preview isinya.
	 *
	 * @return array
	 */
	public function daftar_riwayat()
	{
		$rows = $this->db
			->select('r.id, r.nomor_surat, r.body_final, r.created_at, t.jenis, t.kode_jenis', FALSE)
			->from('riwayat_surat r')
			->join('template_surat t', 't.id = r.template_id')
			->order_by('r.id', 'DESC')
			->limit(self::MAKS_RIWAYAT)
			->get()
			->result_array();

		foreach ($rows as &$r) {
			$r['preview'] = $this->potong($r['body_final'], 110);
			unset($r['body_final']); // daftar ringan — body lengkap diambil per-id
		}
		unset($r);

		return $rows;
	}

	/**
	 * Detail 1 surat dari riwayat (untuk preview ulang & cetak ulang).
	 *
	 * @param  int $id
	 * @return array|null
	 */
	public function riwayat($id)
	{
		$r = $this->db
			->select('r.*, t.jenis, t.kode_jenis', FALSE)
			->from('riwayat_surat r')
			->join('template_surat t', 't.id = r.template_id')
			->where('r.id', (int) $id)
			->get()
			->row();

		if (!$r) {
			return NULL;
		}

		return [
			'id'           => (int) $r->id,
			'nomor_surat'  => $r->nomor_surat,
			'template_id'  => (int) $r->template_id,
			'jenis'        => $r->jenis,
			'kode_jenis'   => $r->kode_jenis,
			'variabel'     => json_decode($r->data_variabel, TRUE) ?: [],
			'body_final'   => $r->body_final,
			'body_html'    => $this->render_html($r->body_final),
			'created_at'   => $r->created_at,
		];
	}

	// =====================================================================
	//  Util
	// =====================================================================

	/**
	 * Ubah teks surat menjadi HTML aman (escape + pertahankan pergantian baris).
	 * Nilai variabel sudah di-escape di sini sehingga bebas XSS saat dirender.
	 *
	 * @param  string $body
	 * @return string
	 */
	public function render_html($body)
	{
		return nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
	}

	/**
	 * Potong teks untuk preview daftar.
	 *
	 * @param  string $teks
	 * @param  int    $panjang
	 * @return string
	 */
	protected function potong($teks, $panjang)
	{
		$teks = trim(preg_replace('/\s+/', ' ', $teks));
		return mb_strlen($teks) > $panjang ? mb_substr($teks, 0, $panjang) . '…' : $teks;
	}
}
