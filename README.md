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

Buka **Pembayaran**, isi **Penghuni atau kamar** untuk mencari sebagian nama penghuni atau nomor kamar. Kombinasikan dengan **Bulan periode**, **Status pembayaran**, dan **Metode pembayaran**, lalu tekan **Terapkan**. Ringkasan transaksi, nominal lunas/belum lunas, dan **Export CSV** mengikuti semua filter yang diterapkan. Gunakan **Reset** untuk menampilkan semua pembayaran. Pencarian tambahan pada tabel hanya mengubah tampilan, bukan isi ekspor; gunakan kolom **Penghuni atau kamar** jika hasil pencarian perlu diekspor.

CSV berisi ID pembayaran, nomor kamar, nama penghuni, periode, tanggal bayar, metode, nominal, dan status. Nominal tidak memakai pemisah ribuan sehingga mudah dijumlahkan. CSV menggunakan UTF-8 dan input yang berpotensi menjadi formula spreadsheet diberi awalan apostrof. Hak **Lihat Data Sewa** diperlukan; ekspor tidak mengirim pesan ke penghuni dan tidak mengubah transaksi. Status `belum_lunas` mencakup tagihan yang belum dibayar maupun pembayaran yang belum disetujui sesuai model saat ini.

## Pengujian dan kelanjutan review

```sh
composer validate --strict
composer check-platform-reqs
composer audit --locked
PAO_DISABLE=1 vendor/bin/phpunit --fail-on-warning --fail-on-risky
npm test
npm ci --ignore-scripts
npm audit --audit-level=low
npm run build
```

PHPUnit memakai SQLite di memori dan data sintetis. Tes mencakup hak akses, keamanan link/dokumen, konflik sewa/reservasi, approval berulang, akhir bulan, ekspor, halaman utama, PDF kontrak, dan alur pendaftaran-pembayaran-approval. GitHub Actions menjalankan pemeriksaan dependency, migrasi database baru, suite tes, build, serta kompilasi route/view pada setiap PR dan perubahan `main`.

**Status operasional penuh belum dinyatakan siap.** Rekonsiliasi data historis pembayaran–invoice–buku keuangan dan persaingan transaksi pada database deployment masih memerlukan verifikasi lanjutan. Baca catatan terbaru dalam [`docs/reviews`](docs/reviews) sebelum melanjutkan pekerjaan. Tes tidak menghubungi layanan WhatsApp, memakai data penghuni asli, atau mengubah server/database produksi.

## Tagihan massal pilihan

Buka **Bulk Billing**, pilih bulan, lalu tampilkan daftar. Hanya sewa berstatus aktif dengan masa tinggal yang bertumpang tindih dengan bulan pilihan yang muncul; tanggal checkout tidak termasuk masa tinggal. Semua sewa yang belum memiliki tagihan dicentang awalnya. Hapus centang pada sewa yang hendak ditunda, lalu tekan **Buat Tagihan Pilihan**. Setidaknya satu sewa harus dipilih. Bila pilihan sudah tidak aktif/berubah periode, muat ulang daftar; seluruh batch ditolak tanpa membuat sebagian tagihan.

Pengulangan bulk pada sewa dan bulan yang sama melewati tagihan yang sudah ada, termasuk tagihan manual pada tanggal lain di bulan tersebut. Nominal tetap `biaya_bulanan` penuh, tanpa prorata; Pembayaran baru melalui link sekarang menyimpan cakupan masa sewa dan mencegah bulk pada bulan yang bertumpang tindih. Pembayaran historis tanpa cakupan masih perlu diperiksa karena nominal tidak cukup untuk menebak masa yang telah ditagih. Transaksi membatalkan seluruh batch beserta invoice baru bila penulisan gagal. Hak **Buat Data Sewa** diperlukan.

Pembayaran berstatus **lunas** tidak dapat diubah atau dihapus, termasuk melalui request langsung. Pembayaran yang belum disetujui tetap dapat diedit. Belum ada alur reversal/refund otomatis: kebutuhan koreksi transaksi lunas perlu dicatat dan direkonsiliasi secara terpisah, bukan mengubah transaksi yang sudah disetujui.

## Keterlacakan invoice otomatis

Setiap pembayaran baru memiliki paling banyak satu invoice otomatis melalui relasi `payment_id`. Daftar **Invoice** menampilkan nomor pembayaran dan kamar asal agar operator dapat menelusuri nominal serta statusnya. Invoice otomatis mengikuti perubahan pembayaran dan tidak dapat diedit atau dihapus secara terpisah; lakukan koreksi pada pembayaran yang belum disetujui. Menghapus pembayaran belum lunas turut menghapus hanya invoice otomatis miliknya. Invoice manual tetap berdiri sendiri dan tidak ditimpa pembayaran pada penghuni/periode yang sama.

Migrasi `2026_10_07_230500_add_payment_id_to_invoices_table` menghubungkan invoice lama berlabel `AUTO:` hanya jika tepat satu pembayaran cocok berdasarkan penghuni dan periode. Data yang ambigu sengaja dibiarkan sebagai **manual/legacy** agar migrasi tidak menebak kepemilikan transaksi. Periksa invoice manual/legacy setelah deployment dan rekonsiliasikan secara administratif; jangan mengubah histori pembayaran lunas. Belum tersedia reversal/refund otomatis.

## Rekonsiliasi invoice dan bukti pembayaran

Buka **Invoice → Rekonsiliasi Invoice**, lalu pilih kategori: invoice `AUTO:` lama tanpa sumber, pembayaran tanpa invoice, atau invoice yang tidak sesuai dengan pembayaran asal. Perbedaan diperiksa pada penghuni, tanggal periode, nominal (termasuk pecahan), dan status lunas. Invoice terkirim untuk pembayaran pending tetap diperbolehkan. Isi **Bulan rekonsiliasi** lalu tekan **Terapkan** untuk membatasi laporan dan jumlah temuan; **Semua bulan** menghapus batas bulan. Untuk invoice tidak sesuai, kedua bulan terdampak diperiksa: bulan invoice maupun bulan pembayaran asal, sehingga pergeseran periode tidak tersembunyi. Filter dipertahankan saat berpindah kategori atau halaman. Setiap halaman memuat maksimal 25 baris. Hak **Lihat Invoice** diperlukan. Laporan hanya membaca data, tidak menebak relasi invoice legacy, tidak menyentuh invoice manual, dan belum mencocokkan buku Keuangan. Jumlah nol bukan bukti kesiapan seluruh aplikasi.

Pembuatan pembayaran manual dan invoice sekarang dilakukan dalam satu transaksi. Jika invoice gagal ditulis, pembayaran ikut dibatalkan dan berkas baru dibersihkan. Penggantian/penghapusan bukti pembayaran lama dilakukan setelah transaksi berhasil commit; jika database gagal, bukti lama dipertahankan. Pembayaran lunas tetap tidak bisa diedit atau dihapus. Kegagalan penghapusan pada penyimpanan berkas setelah commit masih perlu pemantauan/pembersihan administratif; transaksi database tidak dibatalkan setelah commit.

## Pemasukan otomatis dari pembayaran

Saat pemilik menekan **Approve** pada pembayaran, status pembayaran, invoice, status sewa/kamar, dan satu pemasukan **Sewa Kamar** kini disimpan dalam transaksi database yang sama. Pemasukan menampilkan ID pembayaran asal dan masuk ke ringkasan serta laporan Keuangan berdasarkan tanggal bayar. Pengulangan approval tidak membuat pemasukan kedua. Jika pencatatan Keuangan gagal, seluruh approval dibatalkan sehingga pembayaran dan invoice tidak menjadi lunas sebagian.

Pemasukan otomatis tidak dapat diedit atau dihapus dari menu Keuangan; lakukan penelusuran melalui tombol **Pembayaran**. Transaksi Keuangan manual tetap dapat dibuat dan dikelola seperti sebelumnya, tetapi request manual tidak dapat mengaku sebagai sumber pembayaran. Migrasi menambahkan hubungan unik nullable dan tidak menebak hubungan pencatatan lama. Karena itu pembayaran yang sudah lunas sebelum fitur ini serta pemasukan manual terdahulu tetap perlu direkonsiliasi secara administratif agar tidak dibuat ganda. Reversal/refund otomatis belum tersedia.

## Rekonsiliasi pembayaran–Keuangan dan detail transaksi

Buka **Keuangan → Rekonsiliasi Pembayaran–Keuangan**. Dibutuhkan hak **Lihat Keuangan** dan **Lihat Data Sewa**. Kategori pertama memuat pembayaran lunas tanpa sumber pemasukan terhubung; kategori kedua memuat perbedaan nominal, tanggal, jenis, kategori, atau status asal. Filter mengikuti tanggal bayar, dengan periode sebagai fallback bila tanggal bayar kosong; tanggal pemasukan berbeda turut masuk pada kedua bulan terdampak. Pagination berisi 25 baris dan mempertahankan filter.

Laporan tidak menebak relasi atau melakukan backfill. Untuk pembayaran lunas tanpa sumber, sistem menampilkan kandidat pemasukan manual hanya bila tanggal, nominal, jenis `pemasukan`, dan kategori `Sewa Kamar` persis sama. Operator dengan hak **Ubah Keuangan** dan **Ubah Data Sewa** tetap harus memeriksa deskripsi/bukti, memilih kandidat, serta menulis alasan minimal 10 karakter. Tautan membuat pemasukan immutable, membedakannya dari sumber approval pada detail/CSV, dan menyimpan operator, waktu, alasan, serta histori link/unlink. Tautan yang salah dapat dilepas dengan alasan tanpa menghapus payment atau pemasukan; pemasukan approval otomatis tidak dapat dilepas. Detail Keuangan dapat dibaca tanpa izin edit; bukti tetap melalui endpoint privat, dan tautan pembayaran asal mengikuti izin Data Sewa.

Bukti Keuangan manual kini dipertahankan jika perubahan/penghapusan database gagal. Unggahan pengganti yang gagal disimpan dibersihkan; bukti lama dibersihkan setelah commit berhasil. Kegagalan penyimpanan berkas setelah commit tetap membutuhkan pemantauan administratif.

## Ekspor Keuangan dan pemulihan unggahan

Di **Keuangan**, pilih bulan dan jenis transaksi lalu tekan **Export CSV**. Kosongkan bulan untuk semua periode; Reset mengembalikan bulan saat ini. CSV mengikuti bulan/jenis, termasuk semua halaman. Pencarian teks pada tabel tidak mengubah isi ekspor. Nominal memakai dua desimal tanpa pemisah ribuan; sumber manual, otomatis, rekonsiliasi manual, atau pembalikan dan ID pembayaran dapat ditelusuri. CSV tidak berisi path bukti dan melindungi teks yang dapat dibaca sebagai formula spreadsheet. Hak Lihat Keuangan diperlukan.

Pendaftaran/pembayaran melalui link publik sekarang membersihkan unggahan baru bila database gagal, tanpa menghabiskan token; retry dapat dilakukan setelah masalah penyimpanan selesai. Jika storage menolak berkas, formulir menampilkan pesan pada kolom unggahan dan transaksi tidak dinyatakan sukses. Penggantian bukti manual yang gagal tetap mempertahankan bukti asli.

Detail Invoice dan Pembayaran sekarang dapat dibaca dengan hak Lihat masing-masing modul tanpa diarahkan ke halaman Edit. Tautan sumber lintas modul mengikuti izin; transaksi lunas/invoice otomatis tidak menawarkan edit terpisah. Laporan Keuangan HTML/PDF memvalidasi bulan dan tetap tepat saat tanggal sistem 31. Laporan Hunian menghitung kamar unik pada sebagian bulan, mengecualikan tanggal checkout, dan memvalidasi tahun/cakupan; rata-rata harian belum dicakup.

Checkpoint kelanjutan terbaru: [Tautan pemasukan manual historis](docs/reviews/2026-10-09-manual-income-linking.md). Baca checkpoint ini dan [deduplikasi tagihan manual](docs/reviews/2026-10-09-manual-billing-overlap.md) sebelum mengulang backlog.

## Cakupan pembayaran lewat link

Pembayaran baru melalui link menyimpan awal/akhir masa yang ditagih sebagai snapshot. Detail Pembayaran menampilkan **Masa yang ditagih**; tanggal akhir eksklusif. Bulk Billing menganggap semua bulan yang bertumpang tindih sudah ditagih, termasuk pembayaran yang masih menunggu approval. Link lain untuk sewa tersebut tidak membuat tagihan kedua bila cakupan bertumpang tindih atau sudah ada tagihan bulanan pada masa tersebut. Token yang ditolak tetap belum digunakan; hubungi pengelola untuk memakai tagihan yang sudah ada.

Sewa/periode pembayaran pending dengan snapshot tidak boleh dipindahkan melalui Edit; hapus pending dan buat ulang bila sumbernya perlu dikoreksi. Perpanjangan sewa tidak memperluas cakupan tagihan lama, sehingga bulan sesudah akhir cakupan dapat ditagih. Alur link belum menghitung hanya sisa term setelah perpanjangan: gunakan tagihan bulanan untuk sisa masa, jangan mengirim link seluruh term yang bertumpang tindih. Pembayaran lama tidak di-backfill; periode satu bulan tetap menjadi fallback.

Pembayaran manual baru menyimpan cakupan satu bulan dan ditolak bila bertumpang tindih dengan tagihan manual, bulk, atau link pada sewa yang sama. Untuk cicilan atau biaya tambahan yang memang disengaja, centang **Izinkan cicilan atau tagihan tambahan** dan tulis alasan minimal 10 karakter. Pengecualian serta alasannya tampil di Detail Pembayaran untuk audit. Setiap pengecualian tetap membuat payment dan invoice terpisah; sistem belum menghitung saldo cicilan otomatis. Jangan aktifkan pengecualian untuk melewati duplikasi yang tidak disengaja.

## Login untuk operator dengan hak terbatas

Login tanpa tujuan tersimpan membuka menu pertama yang diizinkan untuk **Lihat**; dashboard tetap diprioritaskan bila diizinkan. Akun tanpa izin Lihat menuju Profil Akun. Membuka halaman HTML tanpa izin diarahkan ke menu yang diizinkan, atau menampilkan 403 bila tidak ada menu tersebut. Halaman 403 menyediakan Profil Akun dan Keluar serta petunjuk menghubungi pengelola. Request JSON tetap 403. Tujuan login yang tersimpan tetap diperiksa oleh otorisasi halaman; pemulihan tidak menambah izin akun.

## Histori hunian

Di **Laporan Hunian**, pilih tahun dan **Cakupan sewa → Aktif dan riwayat selesai**, lalu **Tampilkan**. Pilihan bawaan tetap Sewa aktif. Dashboard memakai aktif dan selesai untuk grafik enam bulan. Pergantian penghuni dalam kamar yang sama dihitung sekali per bulan; checkout pada tanggal pertama tidak masuk bulan tersebut. Sewa menunggak, interval kosong/terbalik, dan sewa selesai tanpa checkout tidak dimasukkan. Daftar status kamar di bawah laporan tetap menunjukkan sewa aktif.

Metrik menunjukkan kamar yang dihuni pada sebagian bulan, bukan rata-rata kamar per hari. Persentase dan rata-rata bulan memakai jumlah kamar saat ini; belum merekonstruksi perubahan inventaris kamar atau sewa yang dihapus. Untuk laporan historis yang akurat, pertahankan tanggal masuk/keluar dan riwayat sewa selesai.

## Link bukti untuk tagihan yang sudah ada

Operator dengan hak **Edit Data Sewa** membuka Detail Pembayaran pending lalu menekan **Buat link bukti**. Link berlaku tujuh hari dan satu kali pakai; pembuatan ulang menonaktifkan link lama yang belum dipakai. Kirim link hanya kepada penghuni terkait. Penghuni dapat memilih tunai atau transfer, mengunggah bukti, dan menambahkan keterangan. Transfer wajib memiliki bukti; tunai boleh tanpa unggahan.

Link ini memperbarui tagihan existing tanpa membuat pembayaran/invoice kedua dan tidak menerima perubahan nominal, periode, cakupan, sewa, atau status dari browser. Status tetap menunggu approval pemilik. Tagihan lunas tidak dapat diberi/dipakai link. Penggantian bukti lama dilakukan setelah transaksi commit; kegagalan database/storage mempertahankan bukti/token lama agar aman dicoba ulang. Link seluruh-term dari halaman Sewa tetap untuk membuat tagihan baru bila belum ada cakupan; gunakan link Detail Pembayaran bila bulk/manual sudah membuat tagihan.

## Pembalikan penuh pembayaran

Untuk pembayaran lunas yang seluruh dananya dibatalkan/dikembalikan, buka Detail Pembayaran, isi tanggal serta alasan pada **Pembalikan penuh**, lalu konfirmasi. Sistem mempertahankan pembayaran, invoice lunas, dan pemasukan asli; satu pengeluaran **Pembalikan Pembayaran** dengan nominal sama ditambahkan agar saldo bersih nol. Pemasukan dan pengeluaran pembalikan tidak dapat diedit/dihapus. Detail, CSV, dan buku Keuangan menampilkan hubungan ke pembayaran asal.

Pembalikan hanya tersedia bila pemasukan otomatis asal ada dan konsisten pada nominal, tanggal, jenis, serta kategori. Selesaikan halaman Rekonsiliasi bila ditolak. Tanggal harus dari tanggal bayar sampai hari ini menurut `APP_TIMEZONE` (default `Asia/Jakarta`). Retry tidak membuat pembalikan kedua. Fitur ini hanya mendukung pembalikan penuh; partial refund/cicilan belum didukung. Jangan gunakan penghapusan atau edit manual untuk mengoreksi transaksi lunas.
