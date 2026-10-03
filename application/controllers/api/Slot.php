<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Api/Slot — indikator kuota real-time (FR-03) + heatmap 7 hari (FR-11).
 *
 * GET /api/slot?tanggal=YYYY-MM-DD      → kuota per jam
 * GET /api/slot/heatmap                 → grid 7 hari × 9 jam (satu kali panggil)
 */
class Slot extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('booking_model');
	}

	/**
	 * Kuota seluruh jam pada tanggal tertentu (default: hari ini).
	 */
	public function index()
	{
		$tanggal = $this->input->get('tanggal') ?: date('Y-m-d');

		$d = DateTime::createFromFormat('Y-m-d', $tanggal);
		if (!$d || $d->format('Y-m-d') !== $tanggal) {
			return $this->json_error('Format tanggal tidak valid.', 422, 'VALIDASI');
		}

		return $this->json_ok([
			'tanggal'      => $tanggal,
			'kapasitas'    => Booking_model::KAPASITAS,
			'slot'         => $this->booking_model->kuota_harian($tanggal),
		]);
	}

	/**
	 * FR-11 — Heatmap okupansi 7 hari (hari ini + 6 ke depan).
	 * Satu endpoint, satu panggilan → hemat request.
	 */
	public function heatmap()
	{
		$mulai = $this->input->get('mulai') ?: date('Y-m-d');

		$d = DateTime::createFromFormat('Y-m-d', $mulai);
		if (!$d || $d->format('Y-m-d') !== $mulai) {
			return $this->json_error('Format tanggal tidak valid.', 422, 'VALIDASI');
		}

		// Ambil agregasi 7 hari sekaligus (tanpa N+1 query)
		$sampai = (clone $d)->modify('+6 days')->format('Y-m-d');

		$rows = $this->db->query(
			'SELECT tanggal, jam, COUNT(*) AS terisi
			   FROM bookings
			  WHERE tanggal BETWEEN ? AND ?
			    AND status IN (\'pending\', \'dikonfirmasi\', \'selesai\')
			  GROUP BY tanggal, jam',
			[$mulai, $sampai]
		)->result_array();

		$terisi_map = [];
		foreach ($rows as $r) {
			$terisi_map[$r['tanggal']][$r['jam']] = (int) $r['terisi'];
		}

		$hari  = [];
		$kur   = new DateTime($mulai);
		$today = new DateTime('today');

		for ($i = 0; $i < 7; $i++) {
			$tgl    = $kur->format('Y-m-d');
			$slots  = [];

			foreach (Booking_model::JAM_OPERASIONAL as $jam) {
				$terisi = isset($terisi_map[$tgl][$jam]) ? $terisi_map[$tgl][$jam] : 0;

				// Jam yang sudah lewat pada hari ini → warna abu-abu
				$lewat = ($tgl === $today->format('Y-m-d'))
					&& (strtotime($tgl . ' ' . $jam) < time());

				$slots[] = [
					'jam'       => $jam,
					'jam_label' => $this->booking_model->format_jam($jam),
					'terisi'    => $terisi,
					'sisa'      => Booking_model::KAPASITAS - $terisi,
					'penuh'     => $terisi >= Booking_model::KAPASITAS,
					'lewat'     => $lewat,
				];
			}

			$hari[] = [
				'tanggal'   => $tgl,
				'nama_hari' => $kur->format('D'),
				'label'     => $kur->format('d/m'),
				'hari_ini'  => ($tgl === $today->format('Y-m-d')),
				'slot'      => $slots,
			];

			$kur->modify('+1 day');
		}

		return $this->json_ok([
			'mulai'     => $mulai,
			'sampai'    => $sampai,
			'kapasitas' => Booking_model::KAPASITAS,
			'grid'      => $hari,
		]);
	}
}
