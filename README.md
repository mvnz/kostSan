# kostSan

Aplikasi Laravel untuk operasional kos: kamar, penghuni, reservasi, sewa, pembayaran dan approval, invoice, keuangan, laporan, dokumen privat, hak akses, dan log aktivitas.

## Instalasi lokal

Persyaratan: PHP 8.3+, Composer 2, Node.js 22+, dan SQLite atau database yang didukung Laravel. Aktifkan ekstensi PHP yang diperiksa oleh `composer check-platform-reqs`, termasuk PDO SQLite untuk tes.

```sh
composer install
cp .env.example .env
php artisan key:generate
# Untuk instalasi lokal SQLite baru:
touch database/database.sqlite
php artisan migrate
php artisan kost:admin-create email-anda@example.com --name="Nama Anda"
npm ci --ignore-scripts
npm run build
php artisan serve
```

`kost:admin-create` meminta password dan konfirmasi melalui input tersembunyi; minimal 12 karakter dengan huruf dan angka. Email yang sudah ada tidak diubah. Seeder tidak lagi membuat akun dengan password bawaan. Akun lama tetap tersimpan; bila instalasi sebelumnya memakai akun bawaan, ganti password melalui Profil Akun sebelum membuka akses. `role_id` kosong berarti superadmin sesuai mekanisme hak akses proyek.

Sesuaikan `.env` dengan database masing-masing. Jangan menjalankan migrasi atau seeder dari lingkungan tes ke database operasional. Pada deployment, gunakan HTTPS, `APP_DEBUG=false`, key aplikasi unik, serta cadangan database dan berkas. Dokumen sensitif disimpan di `storage/app/private`; endpoint dokumen memeriksa izin lihat modul terkait.

## Aturan sewa dan reservasi

- Tanggal keluar adalah tanggal checkout: rentang berlaku dari tanggal masuk sampai sebelum tanggal keluar. Checkout dan check-in berikutnya boleh pada tanggal yang sama.
- Sewa aktif atau menunggak dan reservasi dikonfirmasi memblokir periode yang bertumpang tindih. Tanggal akhir kosong memblokir seluruh periode setelah tanggal mulai. Reservasi menunggu/dibatalkan dan sewa selesai tidak memblokir kamar.
- Reservasi yang dikonfirmasi untuk penghuni yang sama boleh dikonversi melalui Data Sewa. Link publik menciptakan penghuni baru sehingga tetap ditolak bila kamar telah dialokasikan.
- Membuat/mengedit sewa, pendaftaran publik, approval, dan perpanjangan tidak boleh menutup sewa penghuni lain. Kamar perbaikan tidak menerima sewa baru. Master Kamar tidak boleh mengosongkan kamar yang masih memiliki sewa; gunakan tindakan Selesai Sewa.
- Perpanjangan memerlukan tepat satu sewa aktif dan tanggal akhir yang lebih maju, dengan pengecekan reservasi lain.
- Approval pembayaran dapat diulang tanpa mengirim notifikasi kedua atau membuka kembali sewa yang selesai. Notifikasi dijalankan setelah transaksi database selesai.
- Pendaftaran menghitung bulan kalender tanpa melewati akhir bulan. Contoh 31 Januari + 3 bulan menjadi 30 April, dengan tagihan 3 bulan. Periode pecahan dan tanggal historis yang tidak tepat bulan kalender mempertahankan hitungan lama (minimal 1 bulan); belum tersedia prorata harian. Harga tier dan pembulatan nominal Rp50.000 dari konfigurasi lama tetap digunakan.

## Ekspor pembayaran untuk rekonsiliasi

Buka **Pembayaran**, pilih **Bulan periode** dan/atau **Status pembayaran**, lalu tekan **Terapkan** dan **Export CSV**. Ringkasan dan CSV mengikuti kedua filter ini. Pencarian pada tabel hanya mengubah tampilan, bukan isi ekspor.

CSV berisi ID pembayaran, nomor kamar, nama penghuni, periode, tanggal bayar, metode, nominal, dan status. Nominal tidak memakai pemisah ribuan sehingga mudah dijumlahkan. CSV menggunakan UTF-8 dan input yang berpotensi menjadi formula spreadsheet diberi awalan apostrof. Hak **Lihat Data Sewa** diperlukan; ekspor tidak mengirim pesan ke penghuni dan tidak mengubah transaksi. Status `belum_lunas` mencakup tagihan yang belum dibayar maupun pembayaran yang belum disetujui sesuai model saat ini.

## Pengujian dan kelanjutan review

```sh
composer validate --strict
composer check-platform-reqs
composer audit --locked
PAO_DISABLE=1 vendor/bin/phpunit --fail-on-warning --fail-on-risky
npm test
npm ci --ignore-scripts
npm run build
```

PHPUnit memakai SQLite di memori dan data sintetis. Tes mencakup hak akses, keamanan link/dokumen, konflik sewa/reservasi, approval berulang, akhir bulan, ekspor, halaman utama, PDF kontrak, dan alur pendaftaran-pembayaran-approval. GitHub Actions menjalankan pemeriksaan dependency, migrasi database baru, suite tes, build, serta kompilasi route/view pada setiap PR dan perubahan `main`.

**Status operasional penuh belum dinyatakan siap.** Rekonsiliasi pembayaran–invoice–buku keuangan, deduplikasi lintas jalur tagihan, dan persaingan transaksi pada database deployment masih memerlukan verifikasi lanjutan. Baca catatan terbaru dalam [`docs/reviews`](docs/reviews) sebelum melanjutkan pekerjaan. Tes tidak menghubungi layanan WhatsApp, memakai data penghuni asli, atau mengubah server/database produksi.
