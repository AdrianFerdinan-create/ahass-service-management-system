<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Api/Report — laporan otomatis (FR-09).
 *
 * GET /api/report?dari=YYYY-MM-DD&sampai=YYYY-MM-DD
 *
 * Mengembalikan rekap bisnis bengkel: total booking, selesai/batal,
 * estimasi pendapatan, okupansi rata-rata slot, breakdown per paket,
 * dan deret harian untuk chart. SELURUH angka dihitung server-side
 * lewat agregasi query (bukan JavaScript mentah).
 *
 * Default rentang: 7 hari terakhir (hari ini s/d 6 hari lampau).
 */
class Report extends MY_Controller {

	/** Batas maksimal rentang laporan (1 tahun) — mencegah query berat. */
	const HARI_MAKS = 366;

	public function __construct()
	{
		parent::__construct();
		$this->load->model('booking_model');
	}

	public function index()
	{
		if ($this->input->method(TRUE) !== 'GET') {
			return $this->json_error('Method tidak diizinkan.', 405, 'METHOD_NOT_ALLOWED');
		}

		// ---------- 1. Ambil + defaultkan rentang ----------
		$dari   = trim((string) $this->input->get('dari'));
		$sampai = trim((string) $this->input->get('sampai'));

		if ($dari === '') {
			$dari = date('Y-m-d', strtotime('-6 days')); // 7 hari termasuk hari ini
		}
		if ($sampai === '') {
			$sampai = date('Y-m-d');
		}

		// ---------- 2. Validasi format tanggal ----------
		foreach (['dari' => $dari, 'sampai' => $sampai] as $k => $v) {
			if (!$this->tanggal_valid($v)) {
				return $this->json_error(
					'Format tanggal tidak valid. Gunakan format YYYY-MM-DD.',
					422,
					'VALIDASI',
					['errors' => [$k => 'Format tanggal tidak valid (YYYY-MM-DD).']]
				);
			}
		}

		// ---------- 3. Validasi urutan & panjang rentang ----------
		if ($dari > $sampai) {
			return $this->json_error(
				'Tanggal awal tidak boleh melebihi tanggal akhir.',
				422,
				'VALIDASI',
				['errors' => ['dari' => 'Tanggal awal melebihi tanggal akhir.']]
			);
		}

		$hari = (int) round((strtotime($sampai) - strtotime($dari)) / 86400) + 1;
		if ($hari > self::HARI_MAKS) {
			return $this->json_error(
				'Rentang laporan maksimal ' . self::HARI_MAKS . ' hari.',
				422,
				'VALIDASI',
				['errors' => ['dari' => 'Rentang terlalu panjang (maks ' . self::HARI_MAKS . ' hari).']]
			);
		}

		// ---------- 4. Agregasi server-side ----------
		$laporan = $this->booking_model->laporan($dari, $sampai);
		$laporan['periode']['label'] = $this->label_periode($dari, $sampai);

		return $this->json_ok($laporan);
	}

	/**
	 * Cek format tanggal ketat (harus persis YYYY-MM-DD & tanggal nyata).
	 *
	 * @param  string $tgl
	 * @return bool
	 */
	protected function tanggal_valid($tgl)
	{
		$d = DateTime::createFromFormat('Y-m-d', $tgl);
		return $d instanceof DateTime && $d->format('Y-m-d') === $tgl;
	}

	/**
	 * Label periode ramah untuk dicetak: "01/10/2026 s/d 07/10/2026 (7 hari)".
	 *
	 * @param  string $dari
	 * @param  string $sampai
	 * @return string
	 */
	protected function label_periode($dari, $sampai)
	{
		$format = function ($tgl) {
			return date('d/m/Y', strtotime($tgl));
		};

		$hari = (int) round((strtotime($sampai) - strtotime($dari)) / 86400) + 1;

		return sprintf('%s s/d %s (%d hari)', $format($dari), $format($sampai), $hari);
	}
}
