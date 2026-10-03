#!/usr/bin/env bash
# ============================================================
# Uji RACE CONDITION (TC-10) — FR-02
# Slot besok 16.00 disiapkan sisa 1 kursi, lalu 6 request
# paralel ditembakkan. Ekspektasi: TEPAT 1 menang, 5 ditolak 409.
# ============================================================
set -u
B="http://localhost/ahass-booking"
JAR="/tmp/ahass_race.txt"
MYSQL="C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql.exe"
BESOK=$(date -d "+1 day" +%F 2>/dev/null || date -v+1d +%F)

curl -s -c "$JAR" "$B/" -o /tmp/race_page.html
TOKEN=$(grep -oP "csrfHash: '\K[^']+" /tmp/race_page.html | head -1)

# Siapkan slot besok 16.00 → isi 2/3 (sisa 1 kursi), tanpa ubah seed lain
"$MYSQL" -uroot -e "USE ahass_booking;
DELETE FROM bookings WHERE plat_nomor LIKE 'B 1%RACE' OR plat_nomor LIKE 'B 2%RACE';
INSERT INTO bookings (kode_booking,plat_nomor,nama_pelanggan,tipe_motor,tanggal,jam,paket_id,status)
VALUES ('AHASS-RACE01','B 1111 RACE','Race Satu','Beat','$BESOK','16:00:00',1,'pending'),
       ('AHASS-RACE02','B 2222 RACE','Race Dua','PCX','$BESOK','16:00:00',1,'pending');"
echo "Slot besok 16.00 disiapkan 2/3 (sisa 1 kursi)"
echo "================================================"

# Tembak 6 request PARALEL dengan plat berbeda
for i in 1 2 3 4 5 6; do
  (
    R=$(curl -s -b "$JAR" -c "$JAR" -w "|%{http_code}" -X POST "$B/api/booking/create" \
      --data-urlencode "ahass_csrf_token=$TOKEN" \
      --data-urlencode "plat_nomor=B ${i}${i}${i}${i} RCE" \
      --data-urlencode "nama_pelanggan=Racer $i" \
      --data-urlencode "tipe_motor=Vario" \
      --data-urlencode "tanggal=$BESOK" \
      --data-urlencode "jam=16:00:00" \
      --data-urlencode "paket_id=1")
    echo "$R" | grep -o '|[0-9]*$' > "/tmp/race_$i.code"
  ) &
done
wait

echo "Respon 6 request paralel:"
SUKSES=0; DITOLAK=0
for i in 1 2 3 4 5 6; do
  K=$(cat "/tmp/race_$i.code" 2>/dev/null)
  echo "  request $i → $K"
  [ "$K" = "|201" ] && SUKSES=$((SUKSES+1))
  [ "$K" = "|409" ] && DITOLAK=$((DITOLAK+1))
done

echo "================================================"
echo "Menang (201): $SUKSES · Ditolak (409): $DITOLAK"

# Verifikasi akhir di DB: total di slot harus TEPAT 3
AKHIR=$("$MYSQL" -uroot -N -e "USE ahass_booking; SELECT COUNT(*) FROM bookings WHERE tanggal='$BESOK' AND jam='16:00:00' AND status IN ('pending','dikonfirmasi','selesai');")
echo "Isi slot di DB SETELAH serbuan: $AKHIR/3"

if [ "$AKHIR" = "3" ] && [ "$SUKSES" = "1" ]; then
  echo "✅ RACE CONDITION AMAN — kuota tidak pernah lebih dari 3"
else
  echo "❌ GAGAL — kuota tembus!"
fi

# Bersihkan data uji (pakai RCE — pola plat uji)
"$MYSQL" -uroot -e "USE ahass_booking; DELETE FROM bookings WHERE plat_nomor LIKE '%RCE' OR kode_booking LIKE 'AHASS-RACE%';" 2>/dev/null
echo "(data uji sudah dibersihkan)"
