/* ══════════════════════════════════════════════════════════
   AHASS BOOKING SERVIS — Aplikasi AJAX (jQuery)
   Semua interaksi TANPA reload halaman.
   T1: slot live + statistik · T2: form booking + struk
   ══════════════════════════════════════════════════════════ */
(function ($) {
	'use strict';

	var H_MAKS = 7; // rentang booking hari ini s/d H+7

	/* ──────────────────────────────────────────────
	   Helper dasar
	   ────────────────────────────────────────────── */

	window.ahassGet = function (url) {
		return $.ajax({ url: window.AHASS.baseUrl + url, method: 'GET', dataType: 'json' });
	};

	window.ahassPost = function (url, data) {
		data = data || {};
		data[window.AHASS.csrfName] = window.AHASS.csrfHash;
		return $.ajax({
			url: window.AHASS.baseUrl + url,
			method: 'POST',
			data: data,
			dataType: 'json'
		});
	};

	window.ahassToast = function (type, message) {
		if (typeof Swal === 'undefined') { alert(message); return; }
		Swal.fire({
			toast: true, position: 'top-end', icon: type, title: message,
			showConfirmButton: false, timer: 3500, timerProgressBar: true
		});
	};

	window.ahassEsc = function (str) {
		if (str === null || str === undefined) return '';
		return $('<div>').text(String(str)).html();
	};

	window.ahassRupiah = function (n) {
		return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
	};

	/** Format tanggal Y-m-d → "Sab, 03 Okt 2026" */
	window.ahassTanggal = function (ymd) {
		if (!ymd) return '-';
		var hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
		var bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
		var d = new Date(ymd + 'T00:00:00');
		return hari[d.getDay()] + ', ' + d.getDate() + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear();
	};

	/** Format jam "10:00:00" → "10.00" */
	window.ahassJam = function (jam) {
		return jam ? jam.substr(0, 2) + '.' + jam.substr(3, 2) : '-';
	};

	function setErr(field, msg) {
		var $f = $('[data-err="' + field + '"]');
		var $in = $('#' + field);
		if (msg) {
			$f.text(msg).addClass('show');
			$in.addClass('is-error');
		} else {
			$f.removeClass('show').text('');
			$in.removeClass('is-error');
		}
	}
	window.ahassSetErr = setErr;

	function clearErrs() {
		$('.ahass-err').removeClass('show').text('');
		$('.ahass-input').removeClass('is-error');
	}

	function loadingBtn($btn, on) {
		if (on) {
			$btn.data('teks', $btn.find('span').text());
			$btn.prop('disabled', true);
			$btn.find('span').text('Memproses…');
			$btn.find('i').removeClass().addClass('fa-solid fa-spinner fa-spin');
		} else {
			$btn.prop('disabled', false);
			if ($btn.data('teks')) $btn.find('span').text($btn.data('teks'));
			$btn.find('i').removeClass().addClass('fa-solid fa-calendar-check');
		}
	}

	/* ──────────────────────────────────────────────
	   FR-03 — Panel kuota slot real-time
	   ────────────────────────────────────────────── */

	function renderSlot(data) {
		var $box = $('#slotList');
		if (!$box.length) return;

		var html = '', kosong = 0;
		var now = new Date();
		var jamSekarang = now.getHours();

		$.each(data.slot, function (i, s) {
			var jamInt = parseInt(s.jam.substr(0, 2), 10);
			var cls = s.penuh ? 'ahass-slot--full' : (s.terisi >= 2 ? 'ahass-slot--warn' : '');
			var pct = Math.round((s.terisi / s.kapasitas) * 100);
			var badge = s.penuh
				? '<i class="fa-solid fa-lock ahass-slot__lock"></i>PENUH'
				: s.terisi + '/' + s.kapasitas;
			var isNow = (jamInt === jamSekarang && data.tanggal === ymdToday());
			var nowTag = isNow ? ' <span class="ahass-slot__now">SEKARANG</span>' : '';

			html += '<div class="ahass-slot ' + cls + '" style="animation-delay:' + (i * 40) + 'ms" ' +
				'title="' + s.jam_label + ' · ' + s.terisi + '/' + s.kapasitas + ' terisi">' +
				'<span class="ahass-slot__jam">' + s.jam_label + nowTag + '</span>' +
				'<span class="ahass-slot__bar"><span class="ahass-slot__fill" style="width:' + pct + '%"></span></span>' +
				'<span class="ahass-slot__badge">' + badge + '</span></div>';

			if (s.sisa > 0) kosong++;
		});

		$box.hide().html(html).fadeIn(250);

		$('#slotTanggalInfo').text(
			(data.tanggal === ymdToday() ? 'Hari ini · ' : data.tanggal + ' · ') + kosong + ' slot masih kosong'
		);
		$('#statSlotKosong').text(kosong);
	}

	function ymdToday() {
		var d = new Date();
		return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
	}

	function ymdPlus(n) {
		var d = new Date();
		d.setDate(d.getDate() + n);
		return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
	}

	function muatSlot(tanggal) {
		if (!$('#slotList').length) return;
		ahassGet('api/slot' + (tanggal ? ('?tanggal=' + tanggal) : ''))
			.done(function (res) { if (res.success) renderSlot(res.data); })
			.fail(function () {
				$('#slotList').html('<div class="ahass-empty" style="padding:28px 16px">' +
					'<span class="ahass-empty__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>' +
					'<h4>Gagal memuat slot</h4><p>Periksa koneksi lalu coba lagi.</p></div>');
			});
	}
	window.ahassMuatSlot = muatSlot;

	/* ──────────────────────────────────────────────
	   Statistik hero
	   ────────────────────────────────────────────── */

	function muatStatistik() {
		if (!$('#statBookingTotal').length) return;
		ahassGet('api/booking/list?page=1').done(function (res) {
			if (res.success) $('#statBookingTotal').text(res.data.total);
		});
	}

	/* ──────────────────────────────────────────────
	   FR-01 — FORM BOOKING (T2)
	   ────────────────────────────────────────────── */

	// Rentang tanggal: hari ini s/d H+7
	function setRentangTanggal() {
		var $t = $('#tanggal');
		if (!$t.length) return;
		$t.attr('min', ymdToday()).attr('max', ymdPlus(H_MAKS)).val(ymdToday());
	}

	// Dropdown paket dari /api/paket
	function muatPaket() {
		var $sel = $('#paket_id');
		if (!$sel.length) return;
		ahassGet('api/paket').done(function (res) {
			if (!res.success) return;
			$.each(res.data.paket, function (i, p) {
				var harga = Number(p.harga) > 0 ? ' — ' + ahassRupiah(p.harga) : ' — sesuai part';
				$sel.append($('<option>').val(p.id).text(p.nama + harga).data('paket', p));
			});
		});
		$sel.on('change', function () {
			var p = $(this).find(':selected').data('paket');
			$('#paketInfo').text(p ? (p.deskripsi || '') : '');
			setErr('paket_id', '');
		});
	}

	// Dropdown jam dari /api/slot — slot penuh disabled + label PENUH
	function muatJam(tanggal, jamTerpilih) {
		var $sel = $('#jam');
		if (!$sel.length) return;

		$sel.prop('disabled', true);
		ahassGet('api/slot?tanggal=' + tanggal).done(function (res) {
			$sel.empty().append($('<option>').val('').text('— pilih jam —'));

			if (res.success) {
				var kosong = 0;
				$.each(res.data.slot, function (i, s) {
					var teks = s.jam_label + '  ·  ' + s.terisi + '/' + s.kapasitas;
					if (s.penuh) {
						teks += '  🔒 PENUH';
						$sel.append($('<option>').val(s.jam).text(teks).prop('disabled', true));
					} else {
						if (s.sisa === 1) teks += '  (sisa 1)';
						$sel.append($('<option>').val(s.jam).text(teks));
						kosong++;
					}
				});

				$('#jamHelp').text(kosong > 0
					? kosong + ' slot tersedia · slot penuh tidak bisa dipilih'
					: 'Semua slot penuh — coba tanggal lain');

				if (jamTerpilih) $sel.val(jamTerpilih);
			}
			$sel.prop('disabled', false);
		});
	}

	// Validasi client-side (server tetap validasi ulang)
	function validasiForm() {
		clearErrs();
		var ok = true;
		var tgl = $('#tanggal').val();
		var jam = $('#jam').val();
		var plat = $.trim($('#plat_nomor').val()).toUpperCase().replace(/\s+/g, ' ');
		var nama = $.trim($('#nama_pelanggan').val());
		var tipe = $('#tipe_motor').val();
		var paket = $('#paket_id').val();
		var hp = $.trim($('#no_hp').val());

		if (!tgl) { setErr('tanggal', 'Pilih tanggal servis.'); ok = false; }
		else if (tgl < ymdToday() || tgl > ymdPlus(H_MAKS)) {
			setErr('tanggal', 'Rentang hanya hari ini s/d H+7.'); ok = false;
		}

		if (!jam) { setErr('jam', 'Pilih jam servis.'); ok = false; }
		if (!plat) { setErr('plat_nomor', 'Nomor polisi wajib diisi.'); ok = false; }
		else if (!/^[A-Z]{1,2}\s?[0-9]{1,4}\s?[A-Z]{0,3}$/.test(plat)) {
			setErr('plat_nomor', 'Format contoh: B 1234 ABC'); ok = false;
		}

		if (!nama) { setErr('nama_pelanggan', 'Nama wajib diisi.'); ok = false; }
		else if (nama.length > 100) { setErr('nama_pelanggan', 'Maks 100 karakter.'); ok = false; }

		if (!tipe) { setErr('tipe_motor', 'Pilih tipe motor.'); ok = false; }
		if (!paket) { setErr('paket_id', 'Pilih paket servis.'); ok = false; }

		if (hp && !/^[0-9+\-\s]{6,20}$/.test(hp)) {
			setErr('no_hp', 'Format No. HP tidak valid.'); ok = false;
		}

		return ok;
	}

	// Submit via AJAX — tanpa reload
	function submitBooking(e) {
		e.preventDefault();
		if (!validasiForm()) {
			ahassToast('warning', 'Lengkapi dulu isian yang ditandai merah.');
			$('.ahass-input.is-error').first().trigger('focus');
			return;
		}

		var $btn = $('#btnBooking');
		loadingBtn($btn, true);

		var payload = {
			tanggal: $('#tanggal').val(),
			jam: $('#jam').val(),
			plat_nomor: $.trim($('#plat_nomor').val()).toUpperCase().replace(/\s+/g, ' '),
			nama_pelanggan: $.trim($('#nama_pelanggan').val()),
			tipe_motor: $('#tipe_motor').val(),
			paket_id: $('#paket_id').val(),
			no_hp: $.trim($('#no_hp').val()),
			catatan: $.trim($('#catatan').val())
		};

		ahassPost('api/booking/create', payload)
			.done(function (res) {
				if (!res.success) return;
				ahassToast('success', 'Booking berhasil! Kode: ' + res.data.kode_booking);
				tampilkanKonfirmasi(res.data);
				resetForm(payload.tanggal);
				muatSlot(payload.tanggal);
				muatStatistik();
			})
			.fail(function (xhr) {
				var res = xhr.responseJSON || {};

				// 409 — slot penuh → tawarkan jam alternatif (FR-10)
				if (xhr.status === 409 && res.code === 'SLOT_PENUH') {
					tawarkanAlternatif(res);
					return;
				}
				// 409 — booking ganda
				if (xhr.status === 409 && res.code === 'BOOKING_GANDA') {
					ahassToast('error', res.message);
					return;
				}
				// 422 — validasi server
				if (xhr.status === 422 && res.errors) {
					$.each(res.errors, function (f, msg) { setErr(f, msg); });
					ahassToast('error', res.message || 'Data belum lengkap.');
					return;
				}
				if (xhr.status === 403) {
					ahassToast('error', 'Sesi kedaluwarsa — muat ulang halaman.');
					return;
				}
				ahassToast('error', res.message || 'Terjadi kesalahan. Coba lagi.');
			})
			.always(function () { loadingBtn($btn, false); });
	}

	// FR-10 — tawarkan max 3 jam alternatif terdekat
	function tawarkanAlternatif(res) {
		var alts = res.alternatives || [];
		var html = '<div style="text-align:left;font-size:14.5px">' +
			ahassEsc(res.message) + '</div>';

		if (alts.length) {
			html += '<div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-top:16px">';
			$.each(alts, function (i, a) {
				html += '<button type="button" class="ahass-btn ahass-btn--primary ahass-alt" ' +
					'data-jam="' + a.jam + '" style="padding:.5rem 1rem;font-size:14px">' +
					'<i class="fa-solid fa-clock"></i> ' + a.jam_label +
					'<small style="opacity:.8;font-weight:600"> (sisa ' + a.sisa + ')</small></button>';
			});
			html += '</div>';
		}

		Swal.fire({
			icon: 'error',
			title: 'Slot Sudah Penuh',
			html: html,
			confirmButtonText: 'Tutup',
			confirmButtonColor: '#E4002B',
			showConfirmButton: true
		});

		muatSlot($('#tanggal').val());
	}

	// Klik jam alternatif → pindahkan dropdown jam
	$(document).on('click', '.ahass-alt', function () {
		var jam = $(this).data('jam');
		$('#jam').val(jam);
		Swal.close();
		ahassToast('info', 'Jam dipindah ke ' + ahassJam(jam) + ' — silakan submit ulang.');
		setTimeout(function () {
			$('#jam').trigger('focus');
		}, 350);
	});

	// FR-07 — kartu konfirmasi
	function tampilkanKonfirmasi(data) {
		var b = data.booking || {};
		var $box = $('#konfirmasiBox');

		var ringkas =
			divRingkas('Tanggal', ahassTanggal(b.tanggal)) +
			divRingkas('Jam', ahassJam(b.jam)) +
			divRingkas('Plat', b.plat_nomor) +
			divRingkas('Motor', b.tipe_motor) +
			divRingkas('Nama', b.nama_pelanggan) +
			divRingkas('Paket', b.nama_paket) +
			divRingkas('Estimasi', Number(b.harga_paket) > 0 ? ahassRupiah(b.harga_paket) : 'Sesuai part');

		var html =
			'<div class="ahass-card ahass-confirm" style="margin-top:8px">' +
			'  <span class="ahass-confirm__check"><i class="fa-solid fa-check"></i></span>' +
			'  <h3>Booking Berhasil!</h3>' +
			'  <p style="color:var(--muted);margin:0">Simpan kode ini — tunjukkan saat tiba di AHASS.</p>' +
			'  <div class="ahass-confirm__kode">' + ahassEsc(data.kode_booking) + '</div>' +
			'  <div class="ahass-confirm__ringkas">' + ringkas + '</div>' +
			'  <div class="d-flex gap-2 justify-content-center flex-wrap">' +
			'    <button type="button" class="ahass-btn ahass-btn--primary" id="btnLihatStruk">' +
			'      <i class="fa-solid fa-receipt"></i> Lihat Struk</button>' +
			'    <button type="button" class="ahass-btn" id="btnBookingLagi" ' +
			'      style="background:#fff;border:1.5px solid var(--line);color:var(--ink-2)">' +
			'      <i class="fa-solid fa-plus"></i> Booking Lagi</button>' +
			'  </div>' +
			'</div>';

		$box.hide().removeClass('d-none').html(html).slideDown(350);

		$box.data('booking', b);
		setTimeout(function () {
			$('html, body').animate({ scrollTop: $box.offset().top - 100 }, 450);
		}, 150);
	}

	function divRingkas(label, nilai) {
		return '<div><span>' + ahassEsc(label) + '</span><span>' + ahassEsc(nilai) + '</span></div>';
	}

	// FR-07 — struk
	function bangunStruk(b) {
		return '<div class="ahass-struk">' +
			'<div class="ahass-struk__kop">' +
			'  <span class="logo">H</span>' +
			'  <b>AHASS</b>' +
			'  <small>Astra Honda Authorized Service Station</small><br>' +
			'  <small>Jl. Raya Contoh No. 88 · Salam Satu HATI</small>' +
			'</div>' +
			'<div class="ahass-struk__judul">STRUK BOOKING SERVIS</div>' +
			row('Kode Booking', b.kode_booking) +
			row('Tanggal', ahassTanggal(b.tanggal)) +
			row('Jam', ahassJam(b.jam)) +
			row('Plat', b.plat_nomor) +
			row('Motor', b.tipe_motor) +
			row('Nama', b.nama_pelanggan) +
			row('HP', b.no_hp || '-') +
			row('Paket', b.nama_paket) +
			(b.catatan ? row('Catatan', b.catatan) : '') +
			'<div class="ahass-struk__total">' +
			row('Estimasi', Number(b.harga_paket) > 0 ? ahassRupiah(b.harga_paket) : 'Sesuai part') +
			'</div>' +
			'<div class="ahass-struk__kode">' + ahassEsc(b.kode_booking) + '</div>' +
			'<div class="ahass-struk__note">Simpan struk ini sebagai bukti booking.<br>' +
			'Datang 15 menit sebelum jam servis. Salam Satu HATI 🤝</div>' +
			'</div>';
	}

	function row(k, v) {
		return '<div class="ahass-struk__row"><span>' + ahassEsc(k) + '</span><span>' + ahassEsc(v) + '</span></div>';
	}

	function resetForm(tanggal) {
		$('#tanggal').val(tanggal || ymdToday());
		$('#jam').val('');
		$('#plat_nomor').val('');
		$('#nama_pelanggan').val('');
		$('#tipe_motor').val('');
		$('#paket_id').val('');
		$('#no_hp').val('');
		$('#catatan').val('');
		$('#paketInfo').text('');
		clearErrs();
		muatJam(tanggal || ymdToday());
	}

	/* ──────────────────────────────────────────────
	   Event binding
	   ────────────────────────────────────────────── */

	$(document).on('submit', '#formBooking', submitBooking);

	// Plat otomatis uppercase + trim
	$(document).on('input', '#plat_nomor', function () {
		var pos = this.selectionStart;
		this.value = this.value.toUpperCase();
		this.setSelectionRange(pos, pos);
		setErr('plat_nomor', '');
	});

	$(document).on('input', '#nama_pelanggan', function () { setErr('nama_pelanggan', ''); });
	$(document).on('change', '#tanggal', function () {
		setErr('tanggal', '');
		muatJam(this.value);
	});
	$(document).on('change', '#jam', function () { setErr('jam', ''); });
	$(document).on('change', '#tipe_motor', function () { setErr('tipe_motor', ''); });

	// Lihat Struk
	$(document).on('click', '#btnLihatStruk', function () {
		var b = $('#konfirmasiBox').data('booking');
		if (!b) return;
		$('#isiStruk').html(bangunStruk(b));
		new bootstrap.Modal('#modalStruk').show();
	});

	// Cetak Struk — hanya struk yang tercetak
	$(document).on('click', '#btnCetakStruk', function () {
		$('#printArea').html($('#isiStruk').html());
		window.print();
	});

	// Booking Lagi
	$(document).on('click', '#btnBookingLagi', function () {
		$('#konfirmasiBox').slideUp(250, function () {
			$(this).addClass('d-none').empty();
		});
		setTimeout(function () {
			$('html, body').animate({ scrollTop: $('#booking').offset().top - 90 }, 400);
			$('#plat_nomor').trigger('focus');
		}, 260);
	});

	// Refresh slot
	$(document).on('click', '#btnRefreshSlot', function () {
		var $i = $(this).find('i');
		$i.addClass('fa-spin');
		muatSlot($('#tanggal').val() || ymdToday());
		setTimeout(function () { $i.removeClass('fa-spin'); }, 700);
	});

	// Smooth scroll anchor dalam-halaman
	$(document).on('click', 'a[href^="#"]', function (e) {
		var id = $(this).attr('href');
		if (id === '#' || id.length < 2) return;
		var target = $(id);
		if (target.length) {
			e.preventDefault();
			$('html, body').animate({ scrollTop: target.offset().top - 84 }, 450);
		}
	});

	/* ──────────────────────────────────────────────
	   FR-04 — TABEL BOOKING + FILTER + PAGINATION (T3)
	   ────────────────────────────────────────────── */

	var filter = { tanggal: '', q: '', paket: '', status: '' };
	var halaman = 1;
	var bookingAktif = null; // booking yang sedang dibuka di modal detail

	function tglPendek(ymd) {
		if (!ymd) return '-';
		var bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
		var d = new Date(ymd + 'T00:00:00');
		return ('0' + d.getDate()).slice(-2) + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear();
	}

	function badgeStatus(s) {
		return '<span class="ahass-badge ahass-badge--' + ahassEsc(s) + '">' + ahassEsc(s) + '</span>';
	}

	function skeletonRows(n) {
		var html = '';
		for (var i = 0; i < n; i++) {
			html += '<tr>' +
				'<td><div class="ahass-skel" style="width:20px"></div></td>' +
				'<td><div class="ahass-skel" style="width:130px"></div></td>' +
				'<td><div class="ahass-skel" style="width:75px"></div></td>' +
				'<td><div class="ahass-skel" style="width:40px"></div></td>' +
				'<td><div class="ahass-skel" style="width:85px"></div></td>' +
				'<td><div class="ahass-skel" style="width:100px"></div></td>' +
				'<td><div class="ahass-skel" style="width:70px"></div></td>' +
				'<td><div class="ahass-skel" style="width:80px"></div></td>' +
				'<td><div class="ahass-skel" style="width:65px"></div></td>' +
				'<td><div class="ahass-skel" style="width:60px"></div></td>' +
				'<td><div class="ahass-skel" style="width:70px;margin-left:auto"></div></td>' +
				'</tr>';
		}
		return html;
	}

	function muatTabel() {
		if (!$('#tbodyBooking').length) return;

		$('#tbodyBooking').html(skeletonRows(6));
		$('#emptyState').addClass('d-none');
		$('#tabelBooking').show();
		$('#infoTotal').text('Memuat…');

		var params = $.param({
			tanggal: filter.tanggal,
			q: filter.q,
			paket: filter.paket,
			status: filter.status,
			page: halaman
		});

		ahassGet('api/booking/list?' + params)
			.done(function (res) {
				if (res.success) renderTabel(res.data);
			})
			.fail(function () {
				$('#tbodyBooking').html(
					'<tr><td colspan="11" style="text-align:center;padding:26px;color:var(--bad)">' +
					'<i class="fa-solid fa-triangle-exclamation"></i> Gagal memuat data — coba lagi.</td></tr>'
				);
			});
	}
	window.ahassMuatTabel = muatTabel;

	function renderTabel(d) {
		if (!d.data.length) {
			$('#tabelBooking').hide();
			$('#emptyState').removeClass('d-none');
			$('#infoTotal').text('0 booking ditemukan');
			$('#infoHalaman').text('');
			$('#pagination').empty();
			return;
		}

		var mulai = (d.halaman - 1) * 10 + 1;
		var rows = '';

		$.each(d.data, function (i, b) {
			var harga = Number(b.harga_paket) > 0
				? ahassRupiah(b.harga_paket)
				: '<span style="color:var(--muted)">Sesuai part</span>';
			var bisaDiubah = (b.status === 'pending' || b.status === 'dikonfirmasi');

			rows += '<tr style="animation-delay:' + (i * 30) + 'ms">' +
				'<td>' + (mulai + i) + '</td>' +
				'<td><span class="ahass-kode">' + ahassEsc(b.kode_booking) + '</span></td>' +
				'<td style="white-space:nowrap">' + tglPendek(b.tanggal) + '</td>' +
				'<td style="font-weight:700">' + ahassJam(b.jam) + '</td>' +
				'<td class="ahass-plat">' + ahassEsc(b.plat_nomor) + '</td>' +
				'<td>' + ahassEsc(b.nama_pelanggan) + '</td>' +
				'<td style="white-space:nowrap">' + ahassEsc(b.tipe_motor) + '</td>' +
				'<td>' + ahassEsc(b.nama_paket) + '</td>' +
				'<td style="white-space:nowrap;font-weight:600">' + harga + '</td>' +
				'<td>' + badgeStatus(b.status) + '</td>' +
				'<td class="text-end" style="white-space:nowrap">' +
					'<button type="button" class="ahass-act" title="Detail" data-detail="' + b.id + '">' +
						'<i class="fa-solid fa-eye"></i></button>' +
					(bisaDiubah ?
						'<button type="button" class="ahass-act" title="Ubah status" data-ubah="' + b.id + '">' +
							'<i class="fa-solid fa-arrows-rotate"></i></button>' +
						'<button type="button" class="ahass-act ahass-act--danger" title="Batalkan" data-batal="' + b.id + '">' +
							'<i class="fa-solid fa-ban"></i></button>' : '') +
				'</td></tr>';
		});

		$('#tbodyBooking').html(rows);
		$('#tabelBooking').show();
		$('#emptyState').addClass('d-none');
		$('#infoTotal').text(
			'Menampilkan ' + mulai + '–' + (mulai + d.data.length - 1) +
			' dari ' + d.total + ' booking'
		);
		renderPagination(d.halaman, d.total_halaman);
	}

	function renderPagination(hal, totalHal) {
		$('#infoHalaman').text('Halaman ' + hal + ' dari ' + totalHal);

		var html = '<li class="page-item"><button ' + (hal <= 1 ? 'disabled ' : '') +
			'data-ke="' + (hal - 1) + '" aria-label="Sebelumnya"><i class="fa-solid fa-chevron-left"></i></button></li>';

		var mulai = Math.max(1, hal - 2);
		var akhir = Math.min(totalHal, mulai + 4);
		mulai = Math.max(1, akhir - 4);

		for (var h = mulai; h <= akhir; h++) {
			html += '<li class="page-item' + (h === hal ? ' active' : '') + '">' +
				'<button data-ke="' + h + '">' + h + '</button></li>';
		}

		html += '<li class="page-item"><button ' + (hal >= totalHal ? 'disabled ' : '') +
			'data-ke="' + (hal + 1) + '" aria-label="Berikutnya"><i class="fa-solid fa-chevron-right"></i></button></li>';

		$('#pagination').html(html);
	}

	function terapkanFilter() {
		filter.tanggal = $('#fTanggal').val() || '';
		filter.status = $('#fStatus').val() || '';
		filter.paket = $('#fPaket').val() || '';
		filter.q = $.trim($('#fCari').val() || '');
		halaman = 1;
		muatTabel();
	}

	function resetFilter() {
		$('#fTanggal, #fStatus, #fPaket, #fCari').val('');
		terapkanFilter();
	}

	function muatPaketFilter() {
		var $sel = $('#fPaket');
		if (!$sel.length || $sel.data('siap')) return;
		ahassGet('api/paket').done(function (res) {
			if (!res.success) return;
			$.each(res.data.paket, function (i, p) {
				$sel.append($('<option>').val(p.id).text(p.nama));
			});
			$sel.data('siap', 1);
		});
	}

	// Debounce pencarian (ketik → jeda 400ms → muat)
	var timerCari;
	$(document).on('input', '#fCari', function () {
		clearTimeout(timerCari);
		timerCari = setTimeout(terapkanFilter, 400);
	});
	$(document).on('keydown', '#fCari', function (e) {
		if (e.key === 'Enter') { e.preventDefault(); clearTimeout(timerCari); terapkanFilter(); }
	});
	$(document).on('change', '#fTanggal, #fStatus, #fPaket', terapkanFilter);
	$(document).on('click', '#btnFilter', terapkanFilter);
	$(document).on('click', '#btnResetFilter, #btnResetDariEmpty', resetFilter);
	$(document).on('click', '#pagination button:not(:disabled)', function () {
		halaman = Number($(this).data('ke'));
		muatTabel();
		$('html, body').animate({ scrollTop: $('#tabelBooking').offset().top - 150 }, 350);
	});

	/* ──────────────────────────────────────────────
	   FR-08 — MODAL DETAIL + AKSI STATUS (T3)
	   ────────────────────────────────────────────── */

	function bukaModal(sel) {
		var el = document.querySelector(sel);
		if (!el) return;
		var inst = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
		inst.show();
	}

	function tutupModal(sel) {
		var el = document.querySelector(sel);
		var inst = el && bootstrap.Modal.getInstance(el);
		if (inst) inst.hide();
	}

	function muatDetail(id, buka) {
		if (buka) {
			$('#isiDetail').html('<div class="ahass-empty" style="border:none;background:transparent">' +
				'<span class="ahass-empty__icon"><i class="fa-solid fa-spinner fa-spin"></i></span>' +
				'<h4>Memuat detail…</h4></div>');
			bukaModal('#modalDetail');
		}

		ahassGet('api/booking/detail?id=' + id).done(function (res) {
			if (!res.success) {
				$('#isiDetail').html('<div class="ahass-empty"><span class="ahass-empty__icon">' +
					'<i class="fa-solid fa-triangle-exclamation"></i></span><h4>Booking tidak ditemukan</h4></div>');
				return;
			}
			bookingAktif = res.data.booking;
			renderDetail(bookingAktif);
		});
	}

	function renderDetail(b) {
		var info = [
			['Nomor Polisi', b.plat_nomor],
			['Nama', b.nama_pelanggan],
			['No. HP', b.no_hp || '-'],
			['Tipe Motor', b.tipe_motor],
			['Tanggal', ahassTanggal(b.tanggal)],
			['Jam', ahassJam(b.jam)],
			['Paket', b.nama_paket],
			['Estimasi', Number(b.harga_paket) > 0 ? ahassRupiah(b.harga_paket) : 'Sesuai part'],
			['Catatan', b.catatan || '-'],
			['Dibuat', (b.created_at || '').replace('T', ' ').substr(0, 16)]
		];

		var rows = '';
		$.each(info, function (i, r) {
			rows += '<div style="display:flex;justify-content:space-between;gap:14px;padding:8px 0;' +
				'border-bottom:1px solid var(--line);font-size:14px">' +
				'<span style="color:var(--muted)">' + ahassEsc(r[0]) + '</span>' +
				'<span style="font-weight:700;text-align:right">' + ahassEsc(r[1]) + '</span></div>';
		});

		var opsiStatus = '';
		$.each(['pending', 'dikonfirmasi', 'selesai', 'dibatalkan'], function (i, s) {
			if (s !== b.status) opsiStatus += '<option value="' + s + '">' + s + '</option>';
		});

		var panelAksi = (b.status === 'pending' || b.status === 'dikonfirmasi')
			? '<div style="margin-top:16px;background:#fff;border:1px solid var(--line);border-radius:12px;padding:15px">' +
				'<label class="ahass-label" for="pilihStatus">Ubah Status Booking</label>' +
				'<div class="d-flex gap-2 flex-wrap">' +
					'<select class="ahass-input" id="pilihStatus" style="flex:1;min-width:160px">' + opsiStatus + '</select>' +
					'<button type="button" class="ahass-btn ahass-btn--primary" id="btnSimpanStatus">' +
						'<i class="fa-solid fa-check"></i> Simpan</button>' +
				'</div>' +
				'<p class="ahass-help mb-0">Membatalkan booking akan <b>mengembalikan kuota slot</b>.</p>' +
			'</div>'
			: '';

		$('#isiDetail').html(
			'<div style="display:flex;justify-content:space-between;align-items:center;gap:10px;' +
				'margin-bottom:14px;flex-wrap:wrap">' +
				'<span class="ahass-kode" style="font-size:15px">' + ahassEsc(b.kode_booking) + '</span>' +
				badgeStatus(b.status) +
			'</div>' +
			'<div style="background:#fff;border:1px solid var(--line);border-radius:12px;padding:6px 15px">' +
				rows + '</div>' +
			panelAksi
		);
	}

	// Buka detail dari tombol mata / tombol ubah status di tabel
	$(document).on('click', '[data-detail]', function () {
		muatDetail(Number($(this).data('detail')), true);
	});
	$(document).on('click', '[data-ubah]', function () {
		muatDetail(Number($(this).data('ubah')), true);
	});

	// Simpan status baru
	$(document).on('click', '#btnSimpanStatus', function () {
		if (!bookingAktif) return;
		var $btn = $(this);
		$btn.prop('disabled', true);

		ahassPost('api/booking/update-status', {
			id: bookingAktif.id,
			status: $('#pilihStatus').val()
		}).done(function (res) {
			ahassToast('success', res.data.message);
			tutupModal('#modalDetail');
			muatTabel();
			muatSlot();
			muatStatistik();
		}).fail(function (xhr) {
			var res = xhr.responseJSON || {};
			ahassToast('error', res.message || 'Gagal memperbarui status.');
		}).always(function () {
			$btn.prop('disabled', false);
		});
	});

	// Konfirmasi batalkan (SweetAlert) — FR-08
	function konfirmasiBatalkan(id, tutupModalDulu) {
		Swal.fire({
			icon: 'warning',
			title: 'Batalkan booking ini?',
			html: 'Slot akan <b>kembali tersedia</b> untuk pelanggan lain.',
			showCancelButton: true,
			confirmButtonText: 'Ya, Batalkan',
			cancelButtonText: 'Kembali',
			confirmButtonColor: '#DC2626',
			reverseButtons: true
		}).then(function (r) {
			if (!r.isConfirmed) return;

			ahassPost('api/booking/update-status', { id: id, status: 'dibatalkan' })
				.done(function (res) {
					ahassToast('success', res.data.message);
					if (tutupModalDulu) tutupModal('#modalDetail');
					muatTabel();
					muatSlot();
					muatStatistik();
				})
				.fail(function (xhr) {
					var res = xhr.responseJSON || {};
					ahassToast('error', res.message || 'Gagal membatalkan booking.');
				});
		});
	}

	$(document).on('click', '[data-batal]', function () {
		konfirmasiBatalkan(Number($(this).data('batal')), false);
	});
	$(document).on('click', '#btnBatalkanDariDetail', function () {
		if (bookingAktif) konfirmasiBatalkan(bookingAktif.id, true);
	});

	// Cetak struk dari modal detail
	$(document).on('click', '#btnCetakDariDetail', function () {
		if (!bookingAktif) return;
		$('#printArea').html(bangunStruk(bookingAktif));
		window.print();
	});

	/* ──────────────────────────────────────────────
	   FR-05 — KARTU STATISTIK + GRAFIK 7 HARI (T4)
	   ────────────────────────────────────────────── */

	function muatStatsDashboard() {
		if (!$('#statBookingHariIni').length) return;

		ahassGet('api/dashboard/stats').done(function (res) {
			if (!res.success) return;
			var k = res.data.kartu;

			$('#statBookingHariIni').text(k.booking_hari_ini);
			$('#statSlotTerisi').text(k.slot_terisi + '/' + k.kapasitas_hari);
			$('#statPendapatan').text(ahassRupiah(k.pendapatan));
			$('#statBatal').text(k.booking_batal);

			renderGrafik(res.data.grafik);
		});
	}

	var chartInstance = null;

	function renderGrafik(data) {
		var canvas = document.getElementById('grafikBooking');
		if (!canvas || typeof Chart === 'undefined') return;

		if (!data || !data.length) {
			$('#grafikWrap').addClass('d-none');
			$('#grafikKosong').removeClass('d-none');
			return;
		}

		var ctx = canvas.getContext('2d');
		var grad = ctx.createLinearGradient(0, 0, 0, 280);
		grad.addColorStop(0, 'rgba(228, 0, 43, .85)');
		grad.addColorStop(1, 'rgba(228, 0, 43, .12)');

		if (chartInstance) chartInstance.destroy();

		chartInstance = new Chart(ctx, {
			type: 'bar',
			data: {
				labels: data.map(function (d) { return d.label; }),
				datasets: [{
					label: 'Booking',
					data: data.map(function (d) { return d.jumlah; }),
					backgroundColor: grad,
					borderColor: '#E4002B',
					borderWidth: 1.5,
					borderRadius: 9,
					maxBarThickness: 46
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: false },
					tooltip: {
						backgroundColor: '#14161A',
						padding: 11,
						cornerRadius: 9,
						displayColors: false,
						callbacks: {
							label: function (c) { return c.parsed.y + ' booking'; }
						}
					}
				},
				scales: {
					y: {
						beginAtZero: true,
						ticks: { stepSize: 1, color: '#7A828F', font: { size: 12 } },
						grid: { color: '#EEF0F3' },
						border: { display: false }
					},
					x: {
						ticks: { color: '#7A828F', font: { size: 12, weight: '600' } },
						grid: { display: false },
						border: { display: false }
					}
				}
			}
		});
	}

	/* ──────────────────────────────────────────────
	   FR-11 — HEATMAP OKUPANSI 7 HARI (T4)
	   ────────────────────────────────────────────── */

	var heatData = null;

	function muatHeatmap() {
		if (!$('#heatmapGrid').length) return;

		ahassGet('api/slot/heatmap').done(function (res) {
			if (!res.success) return;
			heatData = res.data;
			renderHeatmap(res.data);
		});
	}

	function renderHeatmap(d) {
		var html = '<div class="ahass-heat-head"></div>';

		// Header hari
		$.each(d.grid, function (i, h) {
			html += '<div class="ahass-heat-head' + (h.hari_ini ? ' hari-ini' : '') + '">' +
				'<b>' + ahassEsc(h.nama_hari) + '</b>' + ahassEsc(h.label) + '</div>';
		});

		// Baris jam (transposed: baris = jam, kolom = hari)
		var jamPertama = d.grid[0].slot;
		$.each(jamPertama, function (ji, sJam) {
			html += '<div class="ahass-heat-jam">' + sJam.jam_label + '</div>';

			$.each(d.grid, function (hi, hari) {
				var s = hari.slot[ji];
				var cls = s.lewat ? 'lewat'
					: (s.terisi >= 3 ? 'penuh'
						: (s.terisi === 2 ? 'dua'
							: (s.terisi === 1 ? 'satu' : 'kosong')));

				var teks = s.lewat ? '' : s.terisi;
				var tip = s.jam_label + ' · ' + hari.nama_hari + ' ' + hari.label +
					' · ' + (s.lewat ? 'sudah lewat' : s.terisi + '/3 terisi');

				var atribut = s.lewat
					? 'data-lewat="1"'
					: 'data-tgl="' + hari.tanggal + '" data-jam="' + s.jam + '"' +
					  ' data-jamlabel="' + s.jam_label + '" data-hari="' + hari.nama_hari + ' ' + hari.label + '"';

				html += '<div class="ahass-heat-cell ' + cls + '" style="animation-delay:' +
					((hi * 9 + ji) * 12) + 'ms" title="' + ahassEsc(tip) + '" ' + atribut + '>' + teks + '</div>';
			});
		});

		$('#heatmapGrid').html(html);
	}

	// Klik sel heatmap → daftar booking slot tersebut (FR-11)
	$(document).on('click', '.ahass-heat-cell:not([data-lewat])', function () {
		var $c = $(this);
		var tgl = $c.data('tgl'), jam = $c.data('jam');
		var judul = $c.data('jamlabel') + ' · ' + $c.data('hari');

		$('#judulModalSlot').html(
			'<i class="fa-solid fa-clock" style="color:var(--red);margin-right:8px"></i>' +
			ahassEsc(judul)
		);
		$('#isiModalSlot').html('<div class="ahass-empty" style="border:none;background:transparent">' +
			'<span class="ahass-empty__icon"><i class="fa-solid fa-spinner fa-spin"></i></span>' +
			'<h4>Memuat booking…</h4></div>');

		var el = document.querySelector('#modalSlot');
		var inst = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
		inst.show();

		ahassGet('api/booking/list?' + $.param({ tanggal: tgl, jam: jam })).done(function (res) {
			if (!res.success || !res.data.data.length) {
				$('#isiModalSlot').html('<div class="ahass-empty" style="border:none;background:transparent">' +
					'<span class="ahass-empty__icon"><i class="fa-solid fa-inbox"></i></span>' +
					'<h4>Belum ada booking</h4><p>Slot ini masih kosong.</p></div>');
				return;
			}

			var html = '';
			$.each(res.data.data, function (i, b) {
				html += '<div style="background:#fff;border:1px solid var(--line);border-radius:12px;' +
					'padding:13px 15px;margin-bottom:10px;display:flex;justify-content:space-between;' +
					'gap:12px;align-items:center;flex-wrap:wrap">' +
					'<div><span class="ahass-kode">' + ahassEsc(b.kode_booking) + '</span>' +
					'<div style="font-weight:800;margin-top:6px">' + ahassEsc(b.plat_nomor) + '</div>' +
					'<div style="font-size:13px;color:var(--muted)">' + ahassEsc(b.nama_pelanggan) +
					' · ' + ahassEsc(b.tipe_motor) + '</div></div>' +
					badgeStatus(b.status) + '</div>';
			});
			$('#isiModalSlot').html(html);
		});
	});

	/* ──────────────────────────────────────────────
	   FR-12 — RIWAYAT SERVIS BY PLAT (T4)
	   ────────────────────────────────────────────── */

	function cariRiwayat() {
		var plat = $.trim($('#inputPlatRiwayat').val() || '').toUpperCase();
		if (!plat) {
			ahassToast('warning', 'Masukkan nomor polisi dulu.');
			$('#inputPlatRiwayat').trigger('focus');
			return;
		}

		$('#hasilRiwayat').html('<div class="ahass-empty"><span class="ahass-empty__icon">' +
			'<i class="fa-solid fa-spinner fa-spin"></i></span><h4>Mencari riwayat…</h4></div>');

		ahassGet('api/riwayat?plat=' + encodeURIComponent(plat)).done(function (res) {
			if (!res.success) return;
			renderRiwayat(res.data);
		}).fail(function () {
			$('#hasilRiwayat').html('<div class="ahass-empty"><span class="ahass-empty__icon">' +
				'<i class="fa-solid fa-triangle-exclamation"></i></span>' +
				'<h4>Gagal mencari</h4><p>Coba lagi sebentar lagi.</p></div>');
		});
	}

	function renderRiwayat(d) {
		// Plat tidak ditemukan → empty state RAMAH (bukan error merah)
		if (!d.ditemukan) {
			$('#hasilRiwayat').html(
				'<div class="ahass-empty">' +
				'<span class="ahass-empty__icon"><i class="fa-solid fa-motorcycle"></i></span>' +
				'<h4>Belum ada riwayat untuk ' + ahassEsc(d.plat) + '</h4>' +
				'<p>Nomor polisi ini belum pernah booking servis di AHASS. ' +
				'Pastikan pengetikan sudah benar, atau <b>booking sekarang</b> untuk mencatat kunjungan pertama.</p>' +
				'<a href="' + window.AHASS.baseUrl + '" class="ahass-btn ahass-btn--primary mt-2">' +
				'<i class="fa-solid fa-calendar-plus"></i> Booking Servis</a></div>'
			);
			return;
		}

		var r = d.ringkasan;
		var html =
			'<div class="ahass-riwayat-head">' +
			statKartu(r.total_kunjungan, 'Total Kunjungan') +
			statKartu(r.sudah_selesai, 'Sudah Selesai') +
			statKartu(r.tipe_motor, 'Tipe Motor') +
			statKartu(tglPendek(r.terakhir), 'Servis Terakhir') +
			'</div>';

		html += '<div class="table-responsive"><table class="ahass-table"><thead><tr>' +
			'<th>#</th><th>Kode</th><th>Tanggal</th><th>Jam</th><th>Motor</th>' +
			'<th>Paket</th><th>Harga</th><th>Status</th></tr></thead><tbody>';

		$.each(d.riwayat, function (i, b) {
			html += '<tr>' +
				'<td>' + (i + 1) + '</td>' +
				'<td><span class="ahass-kode">' + ahassEsc(b.kode_booking) + '</span></td>' +
				'<td style="white-space:nowrap">' + tglPendek(b.tanggal) + '</td>' +
				'<td style="font-weight:700">' + ahassJam(b.jam) + '</td>' +
				'<td>' + ahassEsc(b.tipe_motor) + '</td>' +
				'<td>' + ahassEsc(b.nama_paket) + '</td>' +
				'<td style="font-weight:600;white-space:nowrap">' +
					(Number(b.harga_paket) > 0 ? ahassRupiah(b.harga_paket) : 'Sesuai part') + '</td>' +
				'<td>' + badgeStatus(b.status) + '</td></tr>';
		});

		html += '</tbody></table></div>';
		$('#hasilRiwayat').html(html);
	}

	function statKartu(nilai, label) {
		return '<div class="ahass-riwayat-stat"><b>' + ahassEsc(nilai) + '</b><span>' +
			ahassEsc(label) + '</span></div>';
	}

	$(document).on('click', '#btnCariRiwayat', cariRiwayat);
	$(document).on('keydown', '#inputPlatRiwayat', function (e) {
		if (e.key === 'Enter') { e.preventDefault(); cariRiwayat(); }
	});
	$(document).on('input', '#inputPlatRiwayat', function () {
		this.value = this.value.toUpperCase();
	});

	/* ──────────────────────────────────────────────
	   FR-09 — LAPORAN OTOMATIS (T5): rekap + cetak + CSV
	   Angka datang sudah dihitung server (/api/report),
		JS hanya merender, mengunduh CSV, dan menyiapkan area cetak.
	   ────────────────────────────────────────────── */

	var laporanData = null;
	var lapChart = null;

	function setPeriodeDefault() {
		$('#lapDari').val(ymdPlus(-6));   // 7 hari termasuk hari ini
		$('#lapSampai').val(ymdToday());
	}

	function muatLaporan() {
		if (!$('#lapDari').length) return;

		var dari   = $('#lapDari').val();
		var sampai = $('#lapSampai').val();
		var $err   = $('#errPeriode');

		if (!dari || !sampai) {
			$err.text('Pilih tanggal awal & akhir terlebih dahulu.').show();
			return;
		}
		if (dari > sampai) {
			$err.text('Tanggal awal tidak boleh melebihi tanggal akhir.').show();
			return;
		}
		$err.hide();

		$('#btnMuatLaporan').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Memuat…');
		if (!laporanData) $('#lapLoading').removeClass('d-none');

		ahassGet('api/report?dari=' + dari + '&sampai=' + sampai)
			.done(function (res) {
				if (!res.success) return;
				renderLaporan(res.data);
				$('#lapLoading').addClass('d-none');
				$('#hasilLaporan').removeClass('d-none');
			})
			.fail(function (xhr) {
				var r = xhr.responseJSON || {};
				ahassToast('error', r.message || 'Gagal memuat laporan.');
				if (!laporanData) $('#lapLoading').addClass('d-none');
			})
			.always(function () {
				$('#btnMuatLaporan').prop('disabled', false).html('<i class="fa-solid fa-rotate"></i> Muat Laporan');
			});
	}

	function renderLaporan(d) {
		laporanData = d;

		var r = d.ringkasan;
		$('#lblPeriode').text(d.periode.label || (d.periode.dari + ' s/d ' + d.periode.sampai));

		$('#repTotal').text(r.total_booking);
		$('#repSelesai').text(r.selesai);
		$('#repBatal').text(r.dibatalkan);
		$('#repPendapatan').text(ahassRupiah(r.estimasi_pendapatan));
		$('#repOkupansi').text(String(r.okupansi_persen).replace('.', ',') + '%');
		$('#repRata').text(String(r.rata2_harian).replace('.', ','));

		// ---------- Tabel breakdown per paket ----------
		var rows = d.per_paket || [];
		var html = '';

		for (var i = 0; i < rows.length; i++) {
			var p = rows[i];
			html += '<tr>' +
				'<td><b>' + ahassEsc(p.nama_paket) + '</b></td>' +
				'<td class="text-center">' + (Number(p.harga) > 0 ? ahassRupiah(p.harga) : 'Sesuai part') + '</td>' +
				'<td class="text-center"><span class="ahass-badge ahass-badge--dikonfirmasi">' + ahassEsc(p.jumlah) + ' booking</span></td>' +
				'<td class="text-end"><b>' + ahassRupiah(p.pendapatan) + '</b></td>' +
				'</tr>';
		}

		if (rows.length) {
			html += '<tr style="background:#FAFBFC">' +
				'<td colspan="3" class="text-end"><b>Total estimasi</b></td>' +
				'<td class="text-end"><b>' + ahassRupiah(r.estimasi_pendapatan) + '</b></td>' +
				'</tr>';
		}

		$('#tbodyPaket').html(html);
		$('#tabelPaket').toggleClass('d-none', !rows.length);
		$('#lapKosong').toggleClass('d-none', !!rows.length);

		renderChartPaket(rows);
	}

	function renderChartPaket(rows) {
		var canvas = document.getElementById('chartPaket');
		if (!canvas || typeof Chart === 'undefined') return;

		if (lapChart) { lapChart.destroy(); lapChart = null; }

		if (!rows.length) {
			$('#lapChartWrap').addClass('d-none');
			$('#lapChartKosong').removeClass('d-none');
			return;
		}

		$('#lapChartWrap').removeClass('d-none');
		$('#lapChartKosong').addClass('d-none');

		var warna = ['#E4002B', '#1A1A1A', '#F59E0B', '#16A34A', '#2563EB', '#7C3AED'];

		lapChart = new Chart(canvas.getContext('2d'), {
			type: 'bar',
			data: {
				labels: rows.map(function (p) { return p.nama_paket; }),
				datasets: [{
					label: 'Booking',
					data: rows.map(function (p) { return Number(p.jumlah); }),
					backgroundColor: rows.map(function (p, i) { return warna[i % warna.length]; }),
					borderRadius: 8,
					maxBarThickness: 26
				}]
			},
			options: {
				indexAxis: 'y',
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: false },
					tooltip: {
						backgroundColor: '#14161A',
						padding: 11,
						cornerRadius: 9,
						displayColors: false,
						callbacks: {
							label: function (c) {
								var p = rows[c.dataIndex];
								return c.parsed.x + ' booking · ' + ahassRupiah(p.pendapatan);
							}
						}
					}
				},
				scales: {
					x: {
						beginAtZero: true,
						ticks: { stepSize: 1, color: '#7A828F', font: { size: 12 } },
						grid: { color: '#EEF0F3' },
						border: { display: false }
					},
					y: {
						ticks: { color: '#1A1A1A', font: { size: 12.5, weight: '600' } },
						grid: { display: false },
						border: { display: false }
					}
				}
			}
		});
	}

	// ---------- Cetak laporan (hanya isi laporan yang tercetak) ----------
	function bangunLaporanPrint() {
		if (!laporanData) return '';

		var d = laporanData, r = d.ringkasan;
		var th = 'border:1px solid #D7DBE1;padding:7px 9px;font-size:12.5px;text-align:left;background:#F5F6F8';
		var td = 'border:1px solid #D7DBE1;padding:7px 9px;font-size:12.5px';
		var h = '';

		h += '<div style="font-family:Arial,Helvetica,sans-serif;color:#111">';

		// Kop surat
		h += '<table style="width:100%;border-collapse:collapse;margin-bottom:16px">' +
			'<tr><td style="vertical-align:middle;padding-bottom:9px;border-bottom:3px solid #E4002B">' +
			'<span style="display:inline-block;width:36px;height:36px;line-height:36px;background:#E4002B;color:#fff;' +
			'text-align:center;border-radius:8px;font-weight:800;font-size:19px;vertical-align:middle">H</span>' +
			'<b style="font-size:18px;margin-left:9px;vertical-align:middle">AHASS — Booking Servis Online</b><br>' +
			'<small style="color:#555">Astra Honda Authorized Service Station · Jl. Raya Contoh No. 88 · Salam Satu HATI</small>' +
			'</td>' +
			'<td style="text-align:right;vertical-align:bottom;padding-bottom:9px;border-bottom:3px solid #E4002B">' +
			'<b style="font-size:15px">LAPORAN BOOKING SERVIS</b><br>' +
			'<small style="color:#555">Periode ' + ahassEsc(d.periode.label) + '</small>' +
			'</td></tr></table>';

		// Ringkasan
		h += '<table style="width:100%;border-collapse:collapse;margin-bottom:16px">' +
			'<tr>' +
			'<th style="' + th + '">Total Booking</th>' +
			'<th style="' + th + '">Selesai</th>' +
			'<th style="' + th + '">Dibatalkan</th>' +
			'<th style="' + th + '">Estimasi Pendapatan</th>' +
			'<th style="' + th + '">Okupansi Slot</th>' +
			'</tr><tr>' +
			'<td style="' + td + '"><b>' + ahassEsc(r.total_booking) + '</b></td>' +
			'<td style="' + td + '">' + ahassEsc(r.selesai) + '</td>' +
			'<td style="' + td + '">' + ahassEsc(r.dibatalkan) + '</td>' +
			'<td style="' + td + '"><b>' + ahassRupiah(r.estimasi_pendapatan) + '</b></td>' +
			'<td style="' + td + '">' + String(r.okupansi_persen).replace('.', ',') + '%</td>' +
			'</tr></table>';

		// Breakdown per paket
		h += '<b style="font-size:13.5px">Breakdown per Paket Servis</b>';
		h += '<table style="width:100%;border-collapse:collapse;margin:7px 0 16px">' +
			'<tr><th style="' + th + '">Paket</th><th style="' + th + '">Harga</th>' +
			'<th style="' + th + ';text-align:center">Booking</th><th style="' + th + ';text-align:right">Estimasi</th></tr>';

		(d.per_paket || []).forEach(function (p) {
			h += '<tr>' +
				'<td style="' + td + '">' + ahassEsc(p.nama_paket) + '</td>' +
				'<td style="' + td + '">' + (Number(p.harga) > 0 ? ahassRupiah(p.harga) : 'Sesuai part') + '</td>' +
				'<td style="' + td + ';text-align:center">' + ahassEsc(p.jumlah) + '</td>' +
				'<td style="' + td + ';text-align:right">' + ahassRupiah(p.pendapatan) + '</td>' +
				'</tr>';
		});

		h += '<tr><td style="' + td + ';background:#F5F6F8"><b>Total</b></td>' +
			'<td style="' + td + ';background:#F5F6F8"></td>' +
			'<td style="' + td + ';background:#F5F6F8;text-align:center"><b>' + ahassEsc(r.booking_aktif) + '</b></td>' +
			'<td style="' + td + ';background:#F5F6F8;text-align:right"><b>' + ahassRupiah(r.estimasi_pendapatan) + '</b></td></tr>';
		h += '</table>';

		// Tanda tangan
		h += '<table style="width:100%;border-collapse:collapse;margin-top:26px"><tr>' +
			'<td style="font-size:12.5px;color:#333">Diekspor pada ' + ahassEsc(new Date().toLocaleString('id-ID')) + '</td>' +
			'<td style="text-align:right;font-size:12.5px">Petugas AHASS,<br><br><br>' +
			'<b>..............................</b></td>' +
			'</tr></table>';
		h += '<div style="text-align:center;font-size:11px;color:#777;margin-top:18px">' +
			'Dokumen ini dihasilkan otomatis oleh Sistem Booking Servis AHASS · Salam Satu HATI</div>';
		h += '</div>';

		return h;
	}

	$(document).on('click', '#btnCetakLaporan', function () {
		if (!laporanData) { ahassToast('warning', 'Muat laporan terlebih dahulu.'); return; }
		$('#printArea').html(bangunLaporanPrint());
		window.print();
	});

	// ---------- Export CSV (tanpa library, sudah ada BOM untuk Excel) ----------
	$(document).on('click', '#btnCsvLaporan', function () {
		if (!laporanData) { ahassToast('warning', 'Muat laporan terlebih dahulu.'); return; }

		var d = laporanData, r = d.ringkasan, lines = [];
		var baris = function (arr) {
			lines.push(arr.map(function (v) {
				return '"' + String(v === null || v === undefined ? '' : v).replace(/"/g, '""') + '"';
			}).join(';'));
		};

		baris(['Laporan Booking Servis AHASS']);
		baris(['Periode', d.periode.label]);
		baris([]);
		baris(['RINGKASAN']);
		baris(['Total Booking', r.total_booking]);
		baris(['Pending', r.pending]);
		baris(['Dikonfirmasi', r.dikonfirmasi]);
		baris(['Selesai', r.selesai]);
		baris(['Dibatalkan', r.dibatalkan]);
		baris(['Estimasi Pendapatan', r.estimasi_pendapatan]);
		baris(['Okupansi Slot (%)', r.okupansi_persen]);
		baris([]);
		baris(['BREAKDOWN PER PAKET']);
		baris(['Paket', 'Harga', 'Booking', 'Estimasi']);
		(d.per_paket || []).forEach(function (p) {
			baris([p.nama_paket, p.harga, p.jumlah, p.pendapatan]);
		});
		baris([]);
		baris(['REKAP HARIAN']);
		baris(['Tanggal', 'Hari', 'Total Booking', 'Tidak Dibatalkan']);
		(d.per_harian || []).forEach(function (h) {
			baris([h.tanggal, h.label, h.total, h.aktif]);
		});

		var isi = '\uFEFF' + lines.join('\r\n');
		var blob = new Blob([isi], { type: 'text/csv;charset=utf-8;' });
		var url  = URL.createObjectURL(blob);
		var a    = document.createElement('a');

		a.href = url;
		a.download = 'laporan-ahass-' + d.periode.dari + '_' + d.periode.sampai + '.csv';
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
		URL.revokeObjectURL(url);

		ahassToast('success', 'CSV laporan berhasil diunduh.');
	});

	// Event filter
	$(document).on('click', '#btnMuatLaporan', muatLaporan);
	$(document).on('change', '#lapDari, #lapSampai', muatLaporan);
	$(document).on('click', '#btnResetPeriode', function () {
		setPeriodeDefault();
		muatLaporan();
	});

	/* ──────────────────────────────────────────────
	   FR-13 — GENERATOR SURAT OTOMATIS (T5.5)
	   Template + variabel dinamis dari /api/surat/*,
		generate → preview berkop → cetak → riwayat.
	   ────────────────────────────────────────────── */

	var suratTemplate = null;   // template terpilih (punya daftar variabel)
	var suratTerakhir  = null;   // surat di preview (untuk cetak / cetak ulang)

	function muatTemplateSurat() {
		if (!$('#pilihTemplate').length) return;

		ahassGet('api/surat/template')
			.done(function (res) {
				if (!res.success) return;

				var list = res.data.template || [];
				if (!list.length) {
					$('#pilihTemplate').html('<option value="">Belum ada template</option>');
					$('#formSurat').html(kosongSurat('fa-file-circle-xmark', 'Belum ada template surat', 'Tambahkan data template lewat database/seed.sql.'));
					return;
				}

				var html = '';
				for (var i = 0; i < list.length; i++) {
					html += '<option value="' + ahassEsc(list[i].id) + '">' + ahassEsc(list[i].jenis) + '</option>';
				}
				$('#pilihTemplate').html(html);

				muatVariabelSurat(list[0].id);
			})
			.fail(function () {
				$('#formSurat').html(kosongSurat('fa-triangle-exclamation', 'Gagal memuat template', 'Muat ulang halaman untuk mencoba lagi.'));
			});
	}

	function kosongSurat(icon, judul, teks) {
		return '<div class="ahass-empty">' +
			'<span class="ahass-empty__icon"><i class="fa-solid ' + icon + '"></i></span>' +
			'<h4>' + ahassEsc(judul) + '</h4><p>' + ahassEsc(teks) + '</p></div>';
	}

	function muatVariabelSurat(id) {
		if (!id) return;

		$('#formSurat').html(kosongSurat('fa-spinner fa-spin', 'Memuat variabel…', 'Mengambil definisi variabel dari server.'));
		$('#btnGenerateSurat, #wrapTanggalSurat').hide();

		ahassGet('api/surat/template?id=' + encodeURIComponent(id))
			.done(function (res) {
				if (!res.success) return;
				suratTemplate = res.data.template;
				renderFormSurat(suratTemplate);
			})
			.fail(function () {
				$('#formSurat').html(kosongSurat('fa-triangle-exclamation', 'Gagal memuat variabel', 'Coba ganti jenis surat.'));
			});
	}

	// Form variabel DINAMIS — dibuat dari variabel_list template terpilih
	function renderFormSurat(t) {
		var html = '';
		for (var i = 0; i < t.variabel.length; i++) {
			var v  = t.variabel[i];
			var id = 'sv_' + v.name;
			html += '<div class="mb-3">' +
				'<label class="ahass-label" for="' + id + '">' + ahassEsc(v.label) +
				(v.required ? '<span class="req">*</span>' : '') + '</label>' +
				'<input type="text" class="ahass-input" id="' + id + '" autocomplete="off" placeholder="' + ahassEsc(v.label) + '">' +
				'<div class="ahass-err" data-err="' + id + '"></div>' +
				'</div>';
		}
		$('#formSurat').html(html);

		$('#infoTemplate').html(
			'<b style="color:var(--ink-2)">' + ahassEsc(t.jenis) + '</b> · kode <code>' + ahassEsc(t.kode_jenis) + '</code><br>' +
			ahassEsc(t.deskripsi || '') + ' — ' + t.variabel.length + ' variabel wajib diisi.'
		);

		$('#tanggalSurat').val(ymdToday());
		$('#wrapTanggalSurat').show();
		$('#btnGenerateSurat').show();
	}

	function generateSurat() {
		if (!suratTemplate) { ahassToast('warning', 'Pilih jenis surat terlebih dahulu.'); return; }

		var payload = {
			template_id   : suratTemplate.id,
			tanggal_surat : $('#tanggalSurat').val() || ymdToday()
		};
		var pertamaKosong = null;

		for (var i = 0; i < suratTemplate.variabel.length; i++) {
			var v   = suratTemplate.variabel[i];
			var id  = 'sv_' + v.name;
			var val = $.trim($('#' + id).val() || '');

			setErr(id, '');
			payload[v.name] = val;

			// Validasi CLIENT (server tetap validasi ulang — TC-22)
			if (v.required && !val) {
				setErr(id, v.label + ' wajib diisi.');
				if (!pertamaKosong) pertamaKosong = id;
			}
		}

		if (pertamaKosong) {
			ahassToast('error', 'Lengkapi variabel yang bertanda * terlebih dahulu.');
			$('#' + pertamaKosong).trigger('focus');
			return;
		}

		var $btn = $('#btnGenerateSurat');
		$btn.prop('disabled', true).find('span').text('Menggenerate…');
		$btn.find('i').removeClass().addClass('fa-solid fa-spinner fa-spin');

		ahassPost('api/surat/generate', payload)
			.done(function (res) {
				if (!res.success) return;
				suratTerakhir = res.data;
				renderPreviewSurat(res.data);
				ahassToast('success', 'Surat berhasil digenerate — ' + res.data.nomor_surat);
				muatRiwayatSurat();
			})
			.fail(function (xhr) {
				var r = xhr.responseJSON || {};
				if (r.errors) {
					$.each(r.errors, function (f, m) { setErr('sv_' + f, m); });
				}
				ahassToast('error', r.message || 'Gagal generate surat.');
			})
			.always(function () {
				$btn.prop('disabled', false).find('span').text('Generate Surat');
				$btn.find('i').removeClass().addClass('fa-solid fa-wand-magic-sparkles');
			});
	}

	function kopSurat() {
		return '<div class="surat-kop">' +
			'<span class="surat-kop__mark">H</span>' +
			'<div>' +
				'<b>AHASS — Anugerah Perdana</b>' +
				'<small>Astra Honda Authorized Service Station</small>' +
				'<small>Jl. Raya Contoh No. 88, Kota · Telp. (0542) 123-456 · service@ahass-anugerah.co.id</small>' +
			'</div>' +
		'</div>';
	}

	function renderPreviewSurat(s) {
		var variabel = s.variabel || {};
		var nama  = variabel.nama_penandatangan || '..............................';
		var jabat = variabel.jabatan_penandatangan || variabel.jabatan || '';

		var html =
			'<div class="surat-leaf" id="isiSurat">' +
				kopSurat() +
				'<div class="surat-nomor">Nomor: <b>' + ahassEsc(s.nomor_surat) + '</b></div>' +
				'<div class="surat-tanggal">' + ahassEsc(s.tanggal_label || '') + '</div>' +
				'<div class="surat-body">' + s.body_html + '</div>' +
				'<div class="surat-ttd">' +
					'<div>Hormat kami,</div>' +
					'<table style="border-collapse:collapse"><tr>' +
						'<td style="padding:54px 46px 0 0;vertical-align:top">' +
							'<div class="surat-ttd__nama">' + ahassEsc(nama) + '</div>' +
							(jabat ? '<div class="surat-ttd__jabatan">' + ahassEsc(jabat) + '</div>' : '') +
						'</td>' +
						'<td style="padding-top:16px;vertical-align:middle"><span class="surat-stempel">STEMPEL<br>AHASS</span></td>' +
					'</tr></table>' +
				'</div>' +
			'</div>';

		$('#previewSurat').html(html);
		$('#aksiSurat').removeClass('d-none').addClass('d-flex');
	}

	function cetakSurat() {
		var isi = $('#isiSurat');
		if (!isi.length) { ahassToast('warning', 'Belum ada surat untuk dicetak.'); return; }
		$('#printArea').html(isi.prop('outerHTML'));
		window.print();
	}

	// ---------- Riwayat surat ----------
	function muatRiwayatSurat() {
		if (!$('#tbodyRiwayat').length) return;

		ahassGet('api/surat/riwayat')
			.done(function (res) {
				if (!res.success) return;
				renderRiwayatSurat(res.data.riwayat || []);
			})
			.fail(function () {
				$('#riwayatLoading').addClass('d-none');
				$('#riwayatKosong').removeClass('d-none').find('h4').text('Gagal memuat riwayat');
			});
	}

	function renderRiwayatSurat(list) {
		$('#riwayatLoading').addClass('d-none');

		if (!list.length) {
			$('#tabelRiwayat').addClass('d-none');
			$('#riwayatKosong').removeClass('d-none');
			return;
		}

		var html = '';
		for (var i = 0; i < list.length; i++) {
			var r   = list[i];
			var tgl = (r.created_at || '').substr(0, 10);
			html += '<tr>' +
				'<td><span class="ahass-kode">' + ahassEsc(r.nomor_surat) + '</span></td>' +
				'<td class="text-nowrap">' + ahassEsc(r.jenis) + '</td>' +
				'<td class="text-nowrap">' + ahassTanggal(tgl) + '</td>' +
				'<td style="color:var(--muted);max-width:320px">' + ahassEsc(r.preview) + '</td>' +
				'<td class="text-end text-nowrap">' +
					'<button type="button" class="ahass-btn ahass-btn--sm" data-lihat="' + ahassEsc(r.id) + '"><i class="fa-solid fa-eye"></i><span>Lihat</span></button> ' +
					'<button type="button" class="ahass-btn ahass-btn--sm ahass-btn--primary" data-cetak="' + ahassEsc(r.id) + '"><i class="fa-solid fa-print"></i><span>Cetak</span></button>' +
				'</td>' +
				'</tr>';
		}

		$('#tbodyRiwayat').html(html);
		$('#tabelRiwayat').removeClass('d-none');
		$('#riwayatKosong').addClass('d-none');
	}

	function bukaRiwayatSurat(id, langsungCetak) {
		ahassGet('api/surat/riwayat?id=' + encodeURIComponent(id))
			.done(function (res) {
				if (!res.success) return;
				suratTerakhir = res.data;
				renderPreviewSurat(res.data);

				if (langsungCetak) {
					cetakSurat();
				} else {
					$('html, body').animate({ scrollTop: $('#previewSurat').offset().top - 90 }, 320);
					ahassToast('success', 'Surat ' + res.data.nomor_surat + ' dibuka kembali.');
				}
			})
			.fail(function () { ahassToast('error', 'Surat tidak ditemukan.'); });
	}

	// ---------- Event FR-13 ----------
	$(document).on('change', '#pilihTemplate', function () { muatVariabelSurat(this.value); });
	$(document).on('click', '#btnGenerateSurat', generateSurat);
	$(document).on('click', '#btnCetakSurat', cetakSurat);
	$(document).on('input', '#formSurat input', function () { setErr(this.id, ''); });
	$(document).on('keydown', '#formSurat input', function (e) {
		if (e.key === 'Enter') { e.preventDefault(); generateSurat(); }
	});
	$(document).on('click', '#tbodyRiwayat [data-lihat]', function () { bukaRiwayatSurat($(this).data('lihat'), false); });
	$(document).on('click', '#tbodyRiwayat [data-cetak]', function () { bukaRiwayatSurat($(this).data('cetak'), true); });
	$(document).on('click', '#btnSuratBaru', function () {
		suratTerakhir = null;
		$('#previewSurat').html(kosongSurat('fa-envelope-open', 'Belum ada surat digenerate', 'Pilih jenis surat, isi variabel, lalu klik Generate Surat.'));
		$('#aksiSurat').addClass('d-none').removeClass('d-flex');
		clearErrs();
		$('html, body').animate({ scrollTop: $('#formSurat').offset().top - 90 }, 300);
		setTimeout(function () { $('#formSurat input').first().trigger('focus'); }, 320);
	});

	/* ──────────────────────────────────────────────
	   Inisialisasi per halaman
	   ────────────────────────────────────────────── */
	$(function () {
		muatSlot();
		muatStatistik();
		setRentangTanggal();
		muatPaket();
		if ($('#jam').length) muatJam(ymdToday());

		// T3 — halaman Daftar Booking
		muatPaketFilter();
		if ($('#tbodyBooking').length) muatTabel();

		// T4 — halaman Dashboard
		muatStatsDashboard();
		muatHeatmap();

		// T5 — halaman Laporan (FR-09)
		if ($('#lapDari').length) {
			setPeriodeDefault();
			muatLaporan();
		}

		// T5.5 — halaman Surat (FR-13)
		if ($('#pilihTemplate').length) {
			muatTemplateSurat();
			muatRiwayatSurat();
		}
	});

})(jQuery);
