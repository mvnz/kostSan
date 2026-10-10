# Checkpoint: perpanjangan dan pengingat sewa menunggak

Tanggal uji: 2026-10-10 (Asia/Jakarta)

## Temuan

Setelah sewa menunggak diperlakukan sebagai hunian berjalan, terdapat dua ketidakkonsistenan lanjutan:

- UI kamar menampilkan aksi perpanjangan untuk kamar terisi, tetapi backend hanya mencari sewa `aktif`, sehingga sewa `menunggak` gagal diperpanjang.
- Dashboard dan command pengingat masa sewa hanya memasukkan status `aktif`, sehingga kontrak menunggak yang segera berakhir tidak masuk follow-up.

## Perbaikan

- Perpanjangan menerima tepat satu sewa `aktif` atau `menunggak`, tetap memakai row lock dan validasi benturan periode.
- Status `menunggak` dipertahankan setelah periode/biaya diperbarui; perpanjangan tidak dianggap pelunasan.
- Dashboard daftar tujuh hari dan command pengingat memasukkan sewa menunggak.
- Dashboard menampilkan badge Menunggak agar follow-up tidak disalahartikan sebagai sewa lancar.
- Bootstrap PHPUnit menghapus route cache build lama sebelum memuat aplikasi, sehingga tes selalu mengeksekusi source route saat ini. Kompilasi route cache tetap diverifikasi setelah suite.

## Bukti

- Sewa menunggak dapat diperpanjang, nominal/periode berubah, status utang dan status kamar tetap konsisten.
- Sewa menunggak masuk dashboard akan berakhir, sedangkan sewa masa depan tidak dihitung sebagai penghuni saat ini.
- Command dengan WhatsApp service palsu mengirim tepat untuk `aktif` dan `menunggak`; `selesai` tidak dikirim. Tidak ada pesan nyata.
- Targeted regression: 26 tes, 155 assertions, lulus.
- Full regression setelah isolasi route cache: 178 tes, 1251 assertions, lulus.

Notifikasi produksi tidak dijalankan. Konfigurasi provider/nomor nyata tidak disentuh.
