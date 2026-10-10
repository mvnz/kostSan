# Checkpoint: indikator reservasi kamar

Tanggal uji: 2026-10-10 (Asia/Jakarta)

## Temuan

Ringkasan kamar menghitung `status = reservasi`, padahal validasi dan enum database kamar hanya mengizinkan `tersedia`, `terisi`, dan `perbaikan`. Akibatnya jumlah reservasi selalu nol dan cabang tampilan reservasi tidak pernah dipakai.

Status fisik kamar tidak diubah menjadi `reservasi`, karena satu kamar yang sedang terisi dapat memiliki reservasi untuk periode mendatang. Benturan periode tetap divalidasi oleh `RoomAvailability` ketika reservasi atau sewa disimpan.

## Perubahan dan kriteria penerimaan

- Tambah relasi `confirmedReservations` untuk reservasi berstatus `dikonfirmasi` yang belum berakhir.
- Ringkasan menghitung jumlah kamar unik dengan reservasi terkonfirmasi, bukan nilai status kamar yang tidak valid.
- Denah menampilkan penanda kalender terpisah tanpa mengubah warna/status fisik kamar.
- Master kamar menampilkan jumlah reservasi terkonfirmasi di samping status fisik.
- Laporan hunian menampilkan calon penghuni serta rencana masuk/keluar dari reservasi terkonfirmasi ketika belum ada sewa aktif.
- Reservasi kedaluwarsa, dibatalkan, dan menunggu tidak ditampilkan sebagai reservasi terkonfirmasi.

Manfaat operasional: operator dapat melihat kamar yang sudah memiliki komitmen reservasi tanpa menyimpulkan bahwa kamar selalu tidak tersedia. Pemilihan tanggal dan validasi benturan tetap menjadi sumber kebenaran untuk ketersediaan periode.

## Bukti uji

- Targeted regression indikator dan laporan hunian: 8 tes, 51 assertions, lulus.
- Full backend: 168 tes, 1186 assertions, lulus.
- Frontend: 4 tes, lulus.
- Vite production build: lulus.
- Route cache dan view cache: lulus.
- Composer validate strict: lulus.

Responsive browser dan UAT manusia belum dilakukan pada checkpoint ini.

## Prioritas lanjutan

1. Validasi responsive di browser ponsel, tablet, dan desktop ketika executable tersedia.
2. Uji concurrency pada database deployment yang representatif.
3. Tinjau kebutuhan tampilan seluruh reservasi mendatang bila satu kamar memiliki lebih dari satu periode yang tidak bertabrakan.
