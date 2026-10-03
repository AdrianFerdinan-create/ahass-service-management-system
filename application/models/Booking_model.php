<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Booking_model — aksi CRUD booking + VALIDASI SLOT (FR-02, FR-03, FR-04).
 *
 * Aturan bisnis utama: kuota maksimal 3 kendaraan per slot jam.
 * Validasi dijalankan DI DALAM transaksi DB (SELECT ... FOR UPDATE)
 * sehingga dua submit di milidetik bersamaan tetap maksimal 3 terisi.
 */
class Booking_model extends CI_Model {

	/** Kuota kendaraan per slot jam (aturan bisnis ujian). */
	const KAPASITAS = 3;

	/** Jam operasional AHASS (9 slot, per 1 jam). */
	const JAM_OPERASIONAL = [
		'08:00:00', '09:00:00', '10:00:00', '11:00:00', '12:00:00',
		'13:00:00', '14:00:00', '15:00:00', '16:00:00',
	];

	/** Status yang mengisi kuota slot (dibatalkan TIDAK menghitung). */
	const STATUS_HITUNG = ['pending', 'dikonfirmasi', 'selesai'];

	/** Status yang boleh dipilih saat ubah status. */
	const STATUS_VALID = ['pending', 'dikonfirmasi', 'selesai', 'dibatalkan'];

	public function __construct()
	{
		parent::__construct();
	}

	// =====================================================================
	//  FR-02 — CREATE dengan validasi slot dalam SATU transaksi
	// =====================================================================

	/**
	 * Simpan booking baru dengan pengecekan kuota anti race condition.
	 *
	 * @param  array $data  Data booking tervalidasi (lihat controller)
	 * @return array        ['ok'=>TRUE,'kode'=>..,'terisi'=>..] | ['ok'=>FALSE,'code'=>..,'message'=>..]
	 */
	public function buat($data)
	{
		$hasil = ['ok' => FALSE];

		// --- Buka transaksi (keluar dari mode auto-commit) ---
		$this->db->trans_begin();

		// Kunci baris pada slot ini sampai transaksi selesai.
		// Baris ke-4 yang mencoba masuk AKAN MENUNGGU sampai transaksi
		// pertama commit, lalu membaca jumlah terbaru (3) → ditolak.
		$terisi = $this->hitung_untuk_update($data['tanggal'], $data['jam']);

		if ($terisi >= self::KAPASITAS) {
			$this->db->trans_rollback();
			return [
				'ok'      => FALSE,
				'code'    => 'SLOT_PENUH',
				'message' => sprintf(
					'Maaf, slot jam %s sudah penuh (%d/%d). Silakan pilih jam lain.',
					$this->format_jam($data['jam']),
					$terisi,
					self::KAPASITAS
				),
				'terisi'  => $terisi,
			];
		}

		// Cek booking ganda: plat sama + tanggal sama + jam sama
		$duplikat = $this->db
			->where('tanggal', $data['tanggal'])
			->where('jam', $data['jam'])
			->where('plat_nomor', $data['plat_nomor'])
			->where("status != 'dibatalkan'", NULL, FALSE)
			->count_all_results('bookings');

		if ($duplikat > 0) {
			$this->db->trans_rollback();
			return [
				'ok'      => FALSE,
				'code'    => 'BOOKING_GANDA',
				'message' => sprintf(
					'Plat %s sudah terdaftar pada tanggal %s jam %s. Tidak boleh booking ganda.',
					$data['plat_nomor'],
					date('d/m/Y', strtotime($data['tanggal'])),
					$this->format_jam($data['jam'])
				),
			];
		}

		// Kode booking unik: AHASS-YYMMDD-XXXX
		$data['kode_booking'] = $this->generate_kode($data['tanggal']);

		$this->db->insert('bookings', $data);

		// Ambil id SEBELUM commit — insert_id() bisa hilang setelah COMMIT
		$id_baru = (int) $this->db->insert_id();

		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			return [
				'ok'      => FALSE,
				'code'    => 'DB_ERROR',
				'message' => 'Gagal menyimpan booking. Silakan coba lagi.',
			];
		}

		$this->db->trans_commit();

		return [
			'ok'       => TRUE,
			'kode'     => $data['kode_booking'],
			'id'       => $id_baru,
			'terisi'   => $terisi + 1,
			'kapasitas'=> self::KAPASITAS,
		];
	}

	/**
	 * SELECT COUNT ... FOR UPDATE — mengunci baris slot selama transaksi.
	 * Hanya menghitung status yang mengisi kuota.
	 *
	 * @param  string $tanggal Y-m-d
	 * @param  string $jam     H:i:s
	 * @return int
	 */
	protected function hitung_untuk_update($tanggal, $jam)
	{
		$query = $this->db->query(
			'SELECT COUNT(*) AS terisi
			   FROM bookings
			  WHERE tanggal = ?
			    AND jam = ?
			    AND status IN (\'pending\', \'dikonfirmasi\', \'selesai\')
			 FOR UPDATE',
			[$tanggal, $jam]
		);

		return (int) $query->row()->terisi;
	}

	/**
	 * Generate kode booking unik AHASS-YYMMDD-XXXX.
	 *
	 * @param  string $tanggal Y-m-d
	 * @return string
	 */
	public function generate_kode($tanggal)
	{
		$prefix = 'AHASS-' . date('ymd', strtotime($tanggal)) . '-';
		$chars  = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // tanpa I/O/0/1 (mudah tertukar)

		for ($i = 0; $i < 20; $i++) {
			$kode = $prefix;
			for ($j = 0; $j < 4; $j++) {
				$kode .= $chars[random_int(0, strlen($chars) - 1)];
			}

			$ada = $this->db
				->where('kode_booking', $kode)
				->count_all_results('bookings');

			if ($ada === 0) {
				return $kode;
			}
		}

		// Cadangan sangat kecil kemungkinan: pakai acak hex
		return $prefix . strtoupper(substr(md5(uniqid('', TRUE)), 0, 4));
	}

	// =====================================================================
	//  FR-03 — KUOTA PER JAM (indikator real-time)
	// =====================================================================

	/**
	 * Kuota seluruh slot pada satu tanggal.
	 *
	 * @param  string $tanggal Y-m-d
	 * @return array   [{jam, kapasitas, terisi, sisa, penuh}]
	 */
	public function kuota_harian($tanggal)
	{
		$rows = $this->db->query(
			'SELECT jam,
			        COUNT(*) AS terisi
			   FROM bookings
			  WHERE tanggal = ?
			    AND status IN (\'pending\', \'dikonfirmasi\', \'selesai\')
			  GROUP BY jam',
			[$tanggal]
		)->result_array();

		$terisi_map = [];
		foreach ($rows as $r) {
			$terisi_map[$r['jam']] = (int) $r['terisi'];
		}

		$hasil = [];
		foreach (self::JAM_OPERASIONAL as $jam) {
			$terisi     = isset($terisi_map[$jam]) ? $terisi_map[$jam] : 0;
			$sisa       = self::KAPASITAS - $terisi;
			$hasil[]    = [
				'jam'        => $jam,
				'jam_label'  => $this->format_jam($jam),
				'kapasitas'  => self::KAPASITAS,
				'terisi'     => $terisi,
				'sisa'       => $sisa,
				'penuh'      => $sisa <= 0,
			];
		}

		return $hasil;
	}

	// =====================================================================
	//  FR-10 — SARAN SLOT ALTERNATIF saat penuh
	// =====================================================================

	/**
	 * Maksimal 3 jam paling dekat pada tanggal sama yang masih ada sisa.
	 *
	 * @param  string $tanggal Y-m-d
	 * @param  string $jam     Jam yang penuh (H:i:s)
	 * @return array   [{jam, jam_label, sisa}]
	 */
	public function slot_alternatif($tanggal, $jam)
	{
		$kuota = $this->kuota_harian($tanggal);
		$urut  = [];

		foreach ($kuota as $slot) {
			if ($slot['sisa'] > 0) {
				$selisih        = abs(strtotime($slot['jam']) - strtotime($jam));
				$urut[]         = $slot + ['_selisih' => $selisih];
			}
		}

		// Paling dekat dulu, batasi 3
		usort($urut, function ($a, $b) {
			return $a['_selisih'] <=> $b['_selisih'];
		});

		return array_slice(array_map(function ($s) {
			return [
				'jam'       => $s['jam'],
				'jam_label' => $s['jam_label'],
				'sisa'      => $s['sisa'],
			];
		}, $urut), 0, 3);
	}

	// =====================================================================
	//  FR-04 — DAFTAR + FILTER + PAGINATION
	// =====================================================================

	/**
	 * Query dasar daftar booking (join paket) dengan filter opsional.
	 *
	 * @param  array $filter {tanggal, q, paket, status}
	 * @return CI_DB_query_builder
	 */
	protected function query_daftar($filter = [])
	{
		$this->db
			->select('b.*, p.nama AS nama_paket, p.harga AS harga_paket', FALSE)
			->from('bookings b')
			->join('paket_servis p', 'p.id = b.paket_id', 'left');

		if (!empty($filter['tanggal'])) {
			$this->db->where('b.tanggal', $filter['tanggal']);
		}

		if (!empty($filter['paket'])) {
			$this->db->where('b.paket_id', (int) $filter['paket']);
		}

		if (!empty($filter['status']) && in_array($filter['status'], self::STATUS_VALID, TRUE)) {
			$this->db->where('b.status', $filter['status']);
		}

		// Filter jam slot (dipakai saat klik sel heatmap)
		if (!empty($filter['jam'])) {
			$this->db->where('b.jam', $filter['jam']);
		}

		// Pencarian: plat / nama / kode booking (FR-07: kode bisa dicari)
		if (!empty($filter['q'])) {
			$this->db->group_start()
				->like('b.plat_nomor', $filter['q'])
				->or_like('b.nama_pelanggan', $filter['q'])
				->or_like('b.kode_booking', $filter['q'])
				->group_end();
		}

		return $this->db;
	}

	/**
	 * Daftar booking terfilter + pagination server-side (10/halaman).
	 *
	 * @param  array   $filter
	 * @param  integer $halaman
	 * @param  integer $per_hal
	 * @return array   {data, total, halaman, total_halaman}
	 */
	public function daftar($filter = [], $halaman = 1, $per_hal = 10)
	{
		// Hitung total dali query filter yang sama
		$total = $this->query_daftar($filter)->count_all_results();

		$halaman = max(1, (int) $halaman);
		$mulai   = ($halaman - 1) * $per_hal;
		$jumlah_halaman = max(1, (int) ceil($total / $per_hal));

		$data = $this->query_daftar($filter)
			->order_by('b.tanggal', 'DESC')
			->order_by('b.jam', 'DESC')
			->order_by('b.id', 'DESC')
			->limit($per_hal, $mulai)
			->get()
			->result_array();

		return [
			'data'          => $data,
			'total'         => $total,
			'halaman'       => $halaman,
			'total_halaman' => $jumlah_halaman,
		];
	}

	/**
	 * Detail 1 booking (untuk modal detail & struk).
	 *
	 * @param  int $id
	 * @return object|null
	 */
	public function detail($id)
	{
		return $this->db
			->select('b.*, p.nama AS nama_paket, p.harga AS harga_paket, p.deskripsi AS deskripsi_paket', FALSE)
			->from('bookings b')
			->join('paket_servis p', 'p.id = b.paket_id', 'left')
			->where('b.id', (int) $id)
			->get()
			->row();
	}

	// =====================================================================
	//  FR-08 — UBAH / BATALKAN STATUS
	// =====================================================================

	/**
	 * Ubah status booking. Memicu perhitungan ulang kuota otomatis
	 * (karena dihitung dari kolom status, bukan tabel terpisah).
	 *
	 * @param  int    $id
	 * @param  string $status
	 * @return array  ['ok'=>bool, 'message'=>string]
	 */
	public function ubah_status($id, $status)
	{
		if (!in_array($status, self::STATUS_VALID, TRUE)) {
			return ['ok' => FALSE, 'message' => 'Status tidak dikenal.'];
		}

		$booking = $this->db
			->where('id', (int) $id)
			->get('bookings')
			->row();

		if (!$booking) {
			return ['ok' => FALSE, 'message' => 'Booking tidak ditemukan.'];
		}

		// Transisi tidak sah: yang sudah selesai / dibatalkan tidak bisa diubah
		if (in_array($booking->status, ['selesai', 'dibatalkan'], TRUE)) {
			return [
				'ok'      => FALSE,
				'message' => sprintf('Booking sudah berstatus "%s" dan tidak bisa diubah lagi.', $booking->status),
			];
		}

		if ($status === 'selesai' && $booking->status !== 'dikonfirmasi') {
			return [
				'ok'      => FALSE,
				'message' => 'Hanya booking berstatus "dikonfirmasi" yang bisa ditandai selesai.',
			];
		}

		$this->db
			->where('id', (int) $id)
			->update('bookings', ['status' => $status]);

		$pesan_status = [
			'dikonfirmasi' => 'Booking dikonfirmasi.',
			'selesai'      => 'Booking ditandai selesai.',
			'dibatalkan'   => 'Booking dibatalkan — slot kembali tersedia.',
			'pending'      => 'Status booking dikembalikan ke pending.',
		];

		return [
			'ok'      => TRUE,
			'message' => isset($pesan_status[$status]) ? $pesan_status[$status] : 'Status diperbarui.',
		];
	}

	// =====================================================================
	//  FR-05 — STATISTIK DASHBOARD + GRAFIK 7 HARI
	// =====================================================================

	/**
	 * Kartu statistik dashboard (semua dihitung untuk HARI INI).
	 *
	 * @return array
	 */
	public function statistik_dashboard()
	{
		$hari_ini = date('Y-m-d');

		// Booking hari ini (semua status, termasuk batal — agar jumlah jujur)
		$booking_hari_ini = (int) $this->db
			->where('tanggal', $hari_ini)
			->count_all_results('bookings');

		// Booking batal hari ini
		$booking_batal = (int) $this->db
			->where('tanggal', $hari_ini)
			->where('status', 'dibatalkan')
			->count_all_results('bookings');

		// Kendaraan mengisi kuota hari ini (batal tidak dihitung)
		$slot_terisi = (int) $this->db
			->where('tanggal', $hari_ini)
			->where("status IN ('pending','dikonfirmasi','selesai')", NULL, FALSE)
			->count_all_results('bookings');

		// Pendapatan estimasi hari ini (booking yang tidak dibatalkan)
		$pendapatan = (float) $this->db
			->select_sum('p.harga', 'total', FALSE)
			->from('bookings b')
			->join('paket_servis p', 'p.id = b.paket_id', 'left')
			->where('b.tanggal', $hari_ini)
			->where("b.status != 'dibatalkan'", NULL, FALSE)
			->get()
			->row()->total ?? 0;

		return [
			'booking_hari_ini' => $booking_hari_ini,
			'slot_terisi'      => $slot_terisi,
			'kapasitas_hari'   => count(self::JAM_OPERASIONAL) * self::KAPASITAS,
			'pendapatan'       => (float) $pendapatan,
			'booking_batal'    => $booking_batal,
		];
	}

	/**
	 * Grafik booking 7 hari (6 hari lampau s/d hari ini).
	 *
	 * @return array  [{tanggal, label, jumlah}]
	 */
	public function grafik_7_hari()
	{
		$dari = date('Y-m-d', strtotime('-6 days'));
		$sampai = date('Y-m-d');

		$rows = $this->db->query(
			'SELECT tanggal, COUNT(*) AS jumlah
			   FROM bookings
			  WHERE tanggal BETWEEN ? AND ?
			    AND status != \'dibatalkan\'
			  GROUP BY tanggal',
			[$dari, $sampai]
		)->result_array();

		$map = [];
		foreach ($rows as $r) {
			$map[$r['tanggal']] = (int) $r['jumlah'];
		}

		$hari_id = ['Sun' => 'Min', 'Mon' => 'Sen', 'Tue' => 'Sel', 'Wed' => 'Rab',
			'Thu' => 'Kam', 'Fri' => 'Jum', 'Sat' => 'Sab'];

		$hasil = [];
		$kur = new DateTime($dari);
		for ($i = 0; $i < 7; $i++) {
			$tgl = $kur->format('Y-m-d');
			$hasil[] = [
				'tanggal' => $tgl,
				'label'   => $hari_id[$kur->format('D')] . ' ' . $kur->format('d/m'),
				'jumlah'  => isset($map[$tgl]) ? $map[$tgl] : 0,
			];
			$kur->modify('+1 day');
		}

		return $hasil;
	}

	// =====================================================================
	//  FR-09 — LAPORAN OTOMATIS (rekap per rentang tanggal)
	// =====================================================================

	/**
	 * Rekap laporan untuk satu rentang tanggal.
	 * SELURUH angka dihitung server-side lewat agregasi query (bukan JS mentah),
	 * sesuai FR-09 — nilai jual: laporan bisnis bengkel, bukan sekadar CRUD.
	 *
	 * @param  string $dari   Y-m-d
	 * @param  string $sampai Y-m-d
	 * @return array  {periode, ringkasan, per_paket, per_harian}
	 */
	public function laporan($dari, $sampai)
	{
		// ---------- 1. Jumlah booking per status ----------
		$status_map = ['pending' => 0, 'dikonfirmasi' => 0, 'selesai' => 0, 'dibatalkan' => 0];

		$rows = $this->db->query(
			'SELECT status, COUNT(*) AS jumlah
			   FROM bookings
			  WHERE tanggal BETWEEN ? AND ?
			  GROUP BY status',
			[$dari, $sampai]
		)->result_array();

		foreach ($rows as $r) {
			if (isset($status_map[$r['status']])) {
				$status_map[$r['status']] = (int) $r['jumlah'];
			}
		}

		$total = array_sum($status_map);
		$aktif = $status_map['pending'] + $status_map['dikonfirmasi'] + $status_map['selesai'];

		// ---------- 2. Estimasi pendapatan + breakdown per paket ----------
		// Hanya booking TIDAK dibatalkan yang dihitung sebagai pendapatan.
		$per_paket = $this->db->query(
			'SELECT p.id AS paket_id,
			        p.nama AS nama_paket,
			        p.harga AS harga,
			        COUNT(b.id) AS jumlah,
			        COALESCE(SUM(p.harga), 0) AS pendapatan
			   FROM bookings b
			   JOIN paket_servis p ON p.id = b.paket_id
			  WHERE b.tanggal BETWEEN ? AND ?
			    AND b.status != \'dibatalkan\'
			  GROUP BY p.id, p.nama, p.harga
			  ORDER BY jumlah DESC, p.nama ASC',
			[$dari, $sampai]
		)->result_array();

		$pendapatan = 0;
		foreach ($per_paket as &$p) {
			$p['jumlah']      = (int) $p['jumlah'];
			$p['harga']       = (float) $p['harga'];
			$p['pendapatan']  = (float) $p['pendapatan'];
			$pendapatan      += $p['pendapatan'];
		}
		unset($p);

		// ---------- 3. Deret per hari (untuk chart rekap) ----------
		$rows_hari = $this->db->query(
			'SELECT tanggal,
			        COUNT(*) AS total,
			        SUM(status != \'dibatalkan\') AS aktif
			   FROM bookings
			  WHERE tanggal BETWEEN ? AND ?
			  GROUP BY tanggal',
			[$dari, $sampai]
		)->result_array();

		$hari_map = [];
		foreach ($rows_hari as $r) {
			$hari_map[$r['tanggal']] = [
				'total' => (int) $r['total'],
				'aktif' => (int) $r['aktif'],
			];
		}

		$hari_id = ['Sun' => 'Min', 'Mon' => 'Sen', 'Tue' => 'Sel', 'Wed' => 'Rab',
			'Thu' => 'Kam', 'Fri' => 'Jum', 'Sat' => 'Sab'];

		$per_harian = [];
		$kur = new DateTime($dari);
		$akhir = new DateTime($sampai);
		while ($kur <= $akhir) {
			$tgl = $kur->format('Y-m-d');
			$per_harian[] = [
				'tanggal' => $tgl,
				'label'   => $hari_id[$kur->format('D')] . ' ' . $kur->format('d/m'),
				'total'   => isset($hari_map[$tgl]) ? $hari_map[$tgl]['total'] : 0,
				'aktif'   => isset($hari_map[$tgl]) ? $hari_map[$tgl]['aktif'] : 0,
			];
			$kur->modify('+1 day');
		}

		// ---------- 4. Okupansi rata-rata slot ----------
		// Okupansi = kendaraan mengisi kuota / total kapasitas seluruh slot pada rentang.
		$jumlah_hari = count($per_harian);
		$kapasitas_total = $jumlah_hari * count(self::JAM_OPERASIONAL) * self::KAPASITAS;
		$okupansi = $kapasitas_total > 0
			? round($aktif / $kapasitas_total * 100, 1)
			: 0.0;

		return [
			'periode' => [
				'dari'   => $dari,
				'sampai' => $sampai,
				'hari'   => $jumlah_hari,
			],
			'ringkasan' => [
				'total_booking'       => $total,
				'pending'             => $status_map['pending'],
				'dikonfirmasi'        => $status_map['dikonfirmasi'],
				'selesai'             => $status_map['selesai'],
				'dibatalkan'          => $status_map['dibatalkan'],
				'booking_aktif'       => $aktif,
				'estimasi_pendapatan' => (float) $pendapatan,
				'okupansi_persen'     => $okupansi,
				'kapasitas_total'     => $kapasitas_total,
				'rata2_harian'        => $jumlah_hari > 0 ? round($aktif / $jumlah_hari, 1) : 0.0,
			],
			'per_paket'  => $per_paket,
			'per_harian' => $per_harian,
		];
	}

	// =====================================================================
	//  FR-12 — RIWAYAT SERVIS BY PLAT
	// =====================================================================

	/**
	 * Riwayat seluruh booking satu plat + ringkasan kunjungan.
	 *
	 * @param  string $plat
	 * @return array  {ditemukan, ringkasan, riwayat}
	 */
	public function riwayat_by_plat($plat)
	{
		$plat = strtoupper(preg_replace('/\s+/', ' ', trim($plat)));

		$rows = $this->db
			->select('b.*, p.nama AS nama_paket, p.harga AS harga_paket', FALSE)
			->from('bookings b')
			->join('paket_servis p', 'p.id = b.paket_id', 'left')
			->where('b.plat_nomor', $plat)
			->order_by('b.tanggal', 'DESC')
			->order_by('b.jam', 'DESC')
			->get()
			->result_array();

		if (!$rows) {
			return ['ditemukan' => FALSE, 'plat' => $plat, 'ringkasan' => NULL, 'riwayat' => []];
		}

		$selesai = 0;
		$tipes = [];
		foreach ($rows as $r) {
			if ($r['status'] === 'selesai') $selesai++;
			$tipes[$r['tipe_motor']] = TRUE;
		}

		return [
			'ditemukan' => TRUE,
			'plat'      => $plat,
			'ringkasan' => [
				'total_kunjungan' => count($rows),
				'sudah_selesai'   => $selesai,
				'tipe_motor'      => implode(', ', array_keys($tipes)),
				'nama'            => $rows[0]['nama_pelanggan'],
				'terakhir'        => $rows[0]['tanggal'],
			],
			'riwayat' => $rows,
		];
	}

	// =====================================================================
	//  Util
	// =====================================================================

	/**
	 * Format jam "10:00:00" → "10.00" (gaya jam Indonesia).
	 *
	 * @param  string $jam
	 * @return string
	 */
	public function format_jam($jam)
	{
		return date('H.i', strtotime($jam));
	}
}
