#!/usr/bin/env bash
# ============================================================
# Uji otomatis API AHASS — T1 Backend Inti
# Jalankan: bash database/test-api.sh
# ============================================================
set -u
B="http://localhost/ahass-booking"
JAR="/tmp/ahass_cookie.txt"
PASS=0; FAIL=0

cek() { # cek "nama" "kode_aktual" "kode_ekspektasi"
  if [ "$2" = "$3" ]; then
    echo "  ✅ $1 → [$2]"
    PASS=$((PASS+1))
  else
    echo "  ❌ $1 → [$2] (ekspektasi [$3])"
    FAIL=$((FAIL+1))
  fi
}

# --- Ambil CSRF token dari halaman ---
curl -s -c "$JAR" "$B/" -o /tmp/ahass_page.html
TOKEN=$(grep -oP "csrfHash: '\K[^']+" /tmp/ahass_page.html | head -1)
echo "CSRF token: ${TOKEN:0:12}..."
echo "================================================"

HARI_INI=$(date +%F)
BESOK=$(date -d "+1 day" +%F 2>/dev/null || date -v+1d +%F)
TGL7=$(date -d "+8 day" +%F 2>/dev/null || date -v+8d +%F)

echo "── TC-01: Booking VALID (slot 08.00 besok) ──"
R=$(curl -s -b "$JAR" -c "$JAR" -w "|%{http_code}" -X POST "$B/api/booking/create" \
  --data-urlencode "ahass_csrf_token=$TOKEN" \
  --data-urlencode "plat_nomor=B 9999 TST" \
  --data-urlencode "nama_pelanggan=Tester Satu" \
  --data-urlencode "tipe_motor=Vario 160" \
  --data-urlencode "tanggal=$BESOK" \
  --data-urlencode "jam=08:00:00" \
  --data-urlencode "paket_id=1" \
  --data-urlencode "no_hp=081200000001")
KODE=$(echo "$R" | sed 's/.*"kode_booking":"\([^"]*\)".*/\1/')
cek "TC-01 create sukses" "$(echo "$R" | grep -o '|[0-9]*$')" "|201"
echo "     kode booking: $KODE"

echo "── TC-02: Slot PENUH (13.00 hari ini sudah 3/3) ──"
R=$(curl -s -b "$JAR" -c "$JAR" -w "|%{http_code}" -X POST "$B/api/booking/create" \
  --data-urlencode "ahass_csrf_token=$TOKEN" \
  --data-urlencode "plat_nomor=B 8888 PNH" \
  --data-urlencode "nama_pelanggan=Tester Dua" \
  --data-urlencode "tipe_motor=PCX" \
  --data-urlencode "tanggal=$HARI_INI" \
  --data-urlencode "jam=13:00:00" \
  --data-urlencode "paket_id=2")
cek "TC-02 slot penuh 409" "$(echo "$R" | grep -o '|[0-9]*$')" "|409"
echo "     pesan: $(echo "$R" | grep -oP '"message":"[^"]*"' | head -1)"
echo "     ada alternatif? $(echo "$R" | grep -c 'alternatives')"

echo "── TC-04: Booking GANDA (plat + tanggal + jam sama) ──"
R=$(curl -s -b "$JAR" -c "$JAR" -w "|%{http_code}" -X POST "$B/api/booking/create" \
  --data-urlencode "ahass_csrf_token=$TOKEN" \
  --data-urlencode "plat_nomor=B 9999 TST" \
  --data-urlencode "nama_pelanggan=Tester Satu Lagi" \
  --data-urlencode "tipe_motor=Vario 160" \
  --data-urlencode "tanggal=$BESOK" \
  --data-urlencode "jam=08:00:00" \
  --data-urlencode "paket_id=1")
cek "TC-04 booking ganda 409" "$(echo "$R" | grep -o '|[0-9]*$')" "|409"
echo "     pesan: $(echo "$R" | grep -oP '"message":"[^"]*"' | head -1)"

echo "── TC-06: Bypass validasi (data kosong) ──"
R=$(curl -s -b "$JAR" -c "$JAR" -w "|%{http_code}" -X POST "$B/api/booking/create" \
  --data-urlencode "ahass_csrf_token=$TOKEN")
cek "TC-06 kosong 422" "$(echo "$R" | grep -o '|[0-9]*$')" "|422"

echo "── TC-11: Rentang tanggal H+8 ──"
R=$(curl -s -b "$JAR" -c "$JAR" -w "|%{http_code}" -X POST "$B/api/booking/create" \
  --data-urlencode "ahass_csrf_token=$TOKEN" \
  --data-urlencode "plat_nomor=B 7777 HGI" \
  --data-urlencode "nama_pelanggan=Tester Tiga" \
  --data-urlencode "tipe_motor=Beat" \
  --data-urlencode "tanggal=$TGL7" \
  --data-urlencode "jam=09:00:00" \
  --data-urlencode "paket_id=1")
cek "TC-11 H+8 ditolak 422" "$(echo "$R" | grep -o '|[0-9]*$')" "|422"

echo "── FR-04: Daftar booking + filter ──"
R=$(curl -s -b "$JAR" -w "|%{http_code}" "$B/api/booking/list?page=1")
cek "list 200" "$(echo "$R" | grep -o '|[0-9]*$')" "|200"
echo "     total: $(echo "$R" | grep -oP '"total":\K[0-9]+')"

R=$(curl -s -b "$JAR" -w "|%{http_code}" "$B/api/booking/list?status=dibatalkan")
echo "  ℹ️  filter status=dibatalkan → total: $(echo "$R" | grep -oP '"total":\K[0-9]+') [$(echo "$R" | grep -o '|[0-9]*$')]"

R=$(curl -s -b "$JAR" -w "|%{http_code}" "$B/api/booking/list?q=B%201199%20PSG")
echo "  ℹ️  pencarian plat 'B 1199 PSG' → total: $(echo "$R" | grep -oP '"total":\K[0-9]+') (riwayat by plat) [$(echo "$R" | grep -o '|[0-9]*$')]"

echo "── FR-10: Slot alternatif pada 13.00 hari ini ──"
R=$(curl -s -b "$JAR" "$B/api/slot?tanggal=$HARI_INI")
echo "$R" | grep -oP '"jam_label":"[^"]*","kapasitas":3,"terisi":3' | head -3

echo "================================================"
echo "HASIL: ✅ $PASS lulus, ❌ $FAIL gagal"

# --- Bersihkan data uji otomatis ---
"C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql.exe" -uroot -e \
  "USE ahass_booking; DELETE FROM bookings WHERE plat_nomor IN ('B 9999 TST','B 8888 PNH','B 7777 HGI');" \
  2>/dev/null && echo "(data uji sudah dibersihkan)"
