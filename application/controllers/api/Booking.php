<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Api/Booking — endpoint booking (FR-01, FR-02, FR-04, FR-07, FR-08, FR-10).
 *
 * POST   /api/booking/create        → 201 sukses · 409 slot penuh/ganda · 422 validasi
 * GET    /api/booking/list          → daftar terfilter + pagination
 * GET    /api/booking/detail?id=    → detail + data struk
 * POST   /api/booking/update-status → ubah/batalkan status
 */
class Booking extends MY_Controller {

	/** Rentang booking: hari ini s/d H+7. */
	const H_MAKS = 7;

	public function __construct()
	{
		parent::__construct();
		$this->load->model('booking_model');
		$this->load->model('paket_model');
		$this->load->library('form_validation');
	}

	// =====================================================================
	//  FR-01 + FR-02 — CREATE
	// =====================================================================
	public function create()
	{
		if ($this->input->method(TRUE) !== 'POST') {
			return $this->json_error('Method tidak diizinkan.', 405, 'METHOD_NOT_ALLOWED');
		}

		// ---------- 1. Validasi SERVER-SIDE (client bisa di-bypass) ----------
		$rules = [
			['field' => 'plat_nomor',     'label' => 'Nomor Polisi', 'rules' => 'required|trim|max_length[20]'],
			['field' => 'nama_pelanggan', 'label' => 'Nama',         'rules' => 'required|trim|max_length[100]'],
			['field' => 'tipe_motor',     'label' => 'Tipe Motor',   'rules' => 'required|trim|max_length[50]'],
			['field' => 'tanggal',        'label' => 'Tanggal',      'rules' => 'required|trim'],
			['field' => 'jam',            'label' => 'Jam',          'rules' => 'required|trim'],
			['field' => 'paket_id',       'label' => 'Paket',        'rules' => 'required|integer'],
			['field' => 'no_hp',          'label' => 'No. HP',       'rules' => 'trim|max_length[20]'],
			['field' => 'catatan',        'label' => 'Catatan',      'rules' => 'trim|max_length[500]'],
		];
		$this->form_validation->set_rules($rules);

		if ($this->form_validation->run() === FALSE) {
			return $this->json_error(
				'Data belum lengkap. Silakan periksa kembali isian Anda.',
				422,
				'VALIDASI',
				['errors' => $this->form_validation->error_array()]
			);
		}

		// Ambil + normalisasi input
		$plat = strtoupper(trim($this->input->post('plat_nomor')));
		$plat = preg_replace('/\s+/', ' ', $plat);

		$tanggal = trim($this->input->post('tanggal'));
		$jam     = trim($this->input->post('jam'));

		// Validasi format & rentang tanggal (hari ini s/d H+7)
		$err_tanggal = $this->validasi_tanggal($tanggal);
		if ($err_tanggal) {
			return $this->json_error($err_tanggal, 422, 'VALIDASI', [
				'errors' => ['tanggal' => $err_tanggal],
			]);
		}

		// Validasi jam harus termasuk slot operasional
		$jam = $this->salin_jam($jam);
		if ($jam === NULL) {
			return $this->json_error('Jam servis tidak valid.', 422, 'VALIDASI', [
				'errors' => ['jam' => 'Jam servis tidak sesuai jam operasional AHASS.'],
			]);
		}

		// Validasi plat sesuai pola kendaraan Indonesia (contoh: B 1234 ABC)
		if (!preg_match('/^[A-Z]{1,2}\s?[0-9]{1,4}\s?[A-Z]{0,3}$/', $plat)) {
			return $this->json_error('Format nomor pola tidak sesuai.', 422, 'VALIDASI', [
				'errors' => ['plat_nomor' => 'Contoh format: B 1234 ABC'],
			]);
		}

		// Paket harus ada & aktif
		$paket = $this->paket_model->ambil((int) $this->input->post('paket_id'));
		if (!$paket) {
			return $this->json_error('Paket servis tidak ditemukan.', 422, 'VALIDASI', [
				'errors' => ['paket_id' => 'Pilih paket servis yang tersedia.'],
			]);
		}

		// ---------- 2. Simpan (validasi kuota DALAM transaksi) ----------
		$booking = [
			'plat_nomor'     => $plat,
			'nama_pelanggan' => trim($this->input->post('nama_pelanggan')),
			'no_hp'          => trim($this->input->post('no_hp')) ?: NULL,
			'tipe_motor'     => trim($this->input->post('tipe_motor')),
			'tanggal'        => $tanggal,
			'jam'            => $jam,
			'paket_id'       => (int) $paket->id,
			'catatan'        => trim($this->input->post('catatan')) ?: NULL,
			'status'         => 'pending',
		];

		$hasil = $this->booking_model->buat($booking);

		// ---------- 3. Respon ----------
		if (!$hasil['ok']) {
			// Slot penuh → sertakan saran jam alternatif (FR-10)
			if ($hasil['code'] === 'SLOT_PENUH') {
				return $this->json_error($hasil['message'], 409, 'SLOT_PENUH', [
					'data' => [
						'terisi'     => $hasil['terisi'],
						'kapasitas'  => Booking_model::KAPASITAS,
						'sisa'       => 0,
					],
					'alternatives' => $this->booking_model->slot_alternatif($tanggal, $jam),
				]);
			}

			return $this->json_error($hasil['message'], 409, $hasil['code']);
		}

		// Muat data lengkap untuk kartu konfirmasi / struk (FR-07)
		$detail = $this->booking_model->detail($hasil['id']);
		$kuota  = $this->booking_model->kuota_harian($tanggal);

		return $this->json_ok([
			'kode_booking' => $hasil['kode'],
			'booking'      => $detail,
			'kuota'        => $kuota,
		], 201);
	}

	// =====================================================================
	//  FR-04 — DAFTAR + FILTER + PAGINATION
	// =====================================================================
	public function list()
	{
		$filter = [
			'tanggal' => $this->input->get('tanggal'),
			'q'       => $this->input->get('q'),
			'paket'   => $this->input->get('paket'),
			'status'  => $this->input->get('status'),
			'jam'     => $this->input->get('jam'),
		];

		// Bersihkan filter kosong agar tidak menghasilkan WHERE aneh
		$filter = array_filter($filter, function ($v) {
			return $v !== NULL && $v !== '';
		});

		$halaman = (int) $this->input->get('page') ?: 1;
		$hasil   = $this->booking_model->daftar($filter, $halaman, 10);

		return $this->json_ok($hasil);
	}

	// =====================================================================
	//  FR-08 — DETAIL (modal) + FR-07 (data struk)
	// =====================================================================
	public function detail()
	{
		$id = (int) $this->input->get('id');

		$booking = $this->booking_model->detail($id);
		if (!$booking) {
			return $this->json_error('Booking tidak ditemukan.', 404, 'NOT_FOUND');
		}

		return $this->json_ok(['booking' => $booking]);
	}

	// =====================================================================
	//  FR-08 — UBAH / BATALKAN STATUS
	// =====================================================================
	public function update_status()
	{
		if ($this->input->method(TRUE) !== 'POST') {
			return $this->json_error('Method tidak diizinkan.', 405, 'METHOD_NOT_ALLOWED');
		}

		$id     = (int) $this->input->post('id');
		$status = $this->input->post('status');

		if ($id <= 0 || !$status) {
			return $this->json_error('Data tidak lengkap.', 422, 'VALIDASI', [
				'errors' => ['id' => 'ID booking dan status wajib diisi.'],
			]);
		}

		$hasil = $this->booking_model->ubah_status($id, $status);

		if (!$hasil['ok']) {
			return $this->json_error($hasil['message'], 409, 'TRANSISI_TIDAK_VALID');
		}

		$tanggal = $this->db->where('id', $id)->get('bookings')->row()->tanggal;

		return $this->json_ok([
			'message' => $hasil['message'],
			'kuota'   => $this->booking_model->kuota_harian($tanggal),
		]);
	}

	// =====================================================================
	//  Util validasi
	// =====================================================================

	/**
	 * Validasi tanggal: format Y-m-d + rentang hari ini s/d H+7.
	 *
	 * @param  string $tanggal
	 * @return string|NULL  pesan error jika invalid
	 */
	protected function validasi_tanggal($tanggal)
	{
		$d = DateTime::createFromFormat('Y-m-d', $tanggal);
		if (!$d || $d->format('Y-m-d') !== $tanggal) {
			return 'Format tanggal tidak valid.';
		}

		$today = new DateTime('today');
		$max   = (clone $today)->modify('+' . self::H_MAKS . ' days');

		if ($d < $today) {
			return 'Tanggal sudah lewat. Booking minimal untuk hari ini.';
		}

		if ($d > $max) {
			return 'Booking maksimal H+7 dari hari ini.';
		}

		return NULL;
	}

	/**
	 * Cocokkan input jam dengan slot operasional → kembalikan H:i:s atau NULL.
	 *
	 * @param  string $jam
	 * @return string|null
	 */
	protected function salin_jam($jam)
	{
		// Terima "10:00", "10.00", "10:00:00"
		$jam = str_replace('.', ':', trim($jam));
		if (preg_match('/^([0-9]{1,2}):([0-9]{2})(:([0-9]{2}))?$/', $jam, $m)) {
			$jam = sprintf('%02d:%02d:00', (int) $m[1], (int) $m[2]);
		}

		return in_array($jam, Booking_model::JAM_OPERASIONAL, TRUE) ? $jam : NULL;
	}
}
