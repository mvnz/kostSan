# Checkpoint: kedaluwarsa reservasi otomatis

Tanggal: 10 Oktober 2026  
Basis awal: `e7b35d6f71af9a344122af7279088efa2296fcc6`

## Temuan

Reservasi terkonfirmasi yang masa rencana tinggalnya sudah berakhir tetap berstatus `dikonfirmasi` selamanya. Indikator kamar memang menyaring sebagian data lama, tetapi batas checkout memakai `>= hari ini`, tidak konsisten dengan aturan interval eksklusif pada `RoomAvailability` (`tanggal_keluar > tanggal mulai`). Daftar reservasi dan metrik tetap menyiratkan komitmen aktif yang sebenarnya selesai.

## Perbaikan

- Command idempotent `reservations:expire` mengubah reservasi terkonfirmasi, belum dikonversi, bertanggal keluar `<= hari ini` menjadi `kedaluwarsa`.
- Setiap record diperiksa ulang dengan lock dan kondisi status di dalam transaksi agar tidak menimpa konversi/perubahan yang menang lebih dulu.
- Scheduler menjalankan command pukul 00.05 waktu aplikasi dengan `withoutOverlapping`.
- Relasi indikator kamar memakai tanggal keluar eksklusif (`> hari ini`) agar hari checkout langsung tersedia untuk indikator periode berikutnya.
- Reservasi tanpa tanggal keluar tidak kedaluwarsa otomatis; operator harus membatalkan atau mengonversinya.

Kriteria penerimaan: kemarin dan hari checkout kedaluwarsa; masa depan/open-ended/menunggu/dikonversi tidak berubah; pengulangan menghasilkan nol perubahan; indikator hari checkout tidak aktif dan besok tetap aktif.

## Bukti

- `ReservationExpiryTest`: 2 tes / 20 assertion, lulus.
- Regresi indikator dan konversi: total targeted 14 tes / 93 assertion, lulus.
- Tidak ada pesan eksternal atau data penghuni nyata yang digunakan.

## Status pengujian

| Kategori | Status | Bukti/batasan |
|---|---|---|
| Functional | LULUS | Status valid, batas tanggal, open-ended, converted, waiting, idempotensi diuji. |
| User flow & transaction | LULUS lokal | Command memeriksa ulang dan mengunci record; konversi sebelumnya tetap terlindungi. Concurrency DB deployment belum diuji. |
| Responsive | BELUM DIUJI | Perubahan UI hanya badge status generik; browser viewport tidak tersedia. |
| Bug & regression | LULUS | Targeted expiry, indikator, dan konversi lulus. |
| Retest | LULUS | Batas checkout `>=` direproduksi lalu diverifikasi menjadi eksklusif. |
| User testing | BELUM DIUJI | Simulasi command dan halaman oleh agen; UAT manusia belum dilakukan. |
| Security | LULUS dalam lingkup | Update bersyarat/locked tidak menimpa converted record; tidak ada input atau pengiriman eksternal. |

## Prioritas berikutnya

Audit konsistensi notifikasi penghuni menunggak dan metrik reservasi dashboard; browser responsive/UAT serta concurrency database deployment tetap kebutuhan terpisah.
