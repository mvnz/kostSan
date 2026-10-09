# Checkpoint: konsistensi kamar untuk sewa menunggak

Tanggal uji: 2026-10-10 (Asia/Jakarta)

## Temuan

Sinkronisasi kamar hanya mencari sewa berstatus `aktif`. Ketika sewa diubah menjadi `menunggak`, kamar dapat ditampilkan sebagai `tersedia`, walaupun `RoomAvailability` tetap menganggap sewa tersebut menempati kamar. Ini menciptakan informasi operasional yang bertentangan antara UI dan validasi backend.

Aksi selesai sewa juga memperbarui sewa dan kamar tanpa satu transaksi, hanya menutup status `aktif`, dan tidak menolak keadaan data ambigu dengan lebih dari satu sewa berjalan.

## Perbaikan

- Status kamar `terisi` kini bersumber dari sewa `aktif` atau `menunggak`.
- Penyelesaian sewa mengunci kamar dan seluruh sewa berjalan dalam transaksi.
- Aksi hanya dilanjutkan jika tepat satu sewa berstatus `aktif` atau `menunggak`; keadaan ambigu ditolak dan di-rollback.
- Sewa menunggak yang diselesaikan berubah menjadi `selesai` dan kamar kembali `tersedia` secara atomik.

## Kriteria dan bukti

- Mengubah sewa menjadi menunggak mempertahankan kamar `terisi`: lulus.
- Menyelesaikan satu sewa menunggak memperbarui kedua record secara atomik: lulus.
- Dua sewa berjalan pada kamar yang sama ditolak tanpa perubahan parsial: lulus.
- Targeted regression: 2 tes, 11 assertions, lulus.

Pengujian concurrency nyata pada engine database deployment tetap diperlukan karena SQLite test tidak merepresentasikan seluruh perilaku row lock produksi.
