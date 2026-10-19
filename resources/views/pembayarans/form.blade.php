@php
$pageTitle = $pembayaran->exists ? 'Edit Pembayaran' : 'Tambah Pembayaran';
$pageSubtitle = 'Form pembayaran sewa penghuni.';
@endphp
@extends('layouts.app')

@section('content')
<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-money"></i></div>
    <div>
        <h2>{{ $pembayaran->exists ? 'Edit Pembayaran' : 'Tambah Pembayaran' }}</h2>
        <p>Isi detail periode dan nominal pembayaran.</p>
    </div>
</div>
@if($pembayaran->status === 'lunas')
<div class="alert alert-info">Pembayaran sudah disetujui. Data ini hanya dapat dilihat dan tidak dapat diubah atau dihapus.</div>
@endif
<div class="card"><div class="card-body"><form method="POST" action="{{ $pembayaran->exists ? route('pembayarans.update', $pembayaran) : route('pembayarans.store') }}" enctype="multipart/form-data">
@csrf @if($pembayaran->exists) @method('PUT') @endif
<div class="mb-3"><label class="form-label">Data Sewa</label><select name="sewa_id" class="form-select" required><option value="">Pilih data sewa</option>@foreach($sewas as $sewa)<option value="{{ $sewa->id }}" @selected((string) old('sewa_id', $pembayaran->sewa_id) === (string) $sewa->id)>{{ $sewa->penghuni->nama ?? '-' }} - Kamar {{ $sewa->kamar->nomor ?? '-' }}</option>@endforeach</select></div>
<div class="row"><div class="col-md-4 mb-3"><label class="form-label">Periode</label><input type="date" name="periode" class="form-control @error('periode') is-invalid @enderror" value="{{ old('periode', optional($pembayaran->periode)->format('Y-m-d')) }}" required>@error('periode')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="col-md-4 mb-3"><label class="form-label">Tanggal Bayar</label><input type="date" name="tanggal_bayar" class="form-control" value="{{ old('tanggal_bayar', optional($pembayaran->tanggal_bayar)->format('Y-m-d')) }}"></div><div class="col-md-4 mb-3"><label class="form-label">Metode</label><select name="metode" class="form-select" required>@foreach(['cash','transfer','e-wallet'] as $metode)<option value="{{ $metode }}" @selected(old('metode', $pembayaran->metode ?? 'transfer') === $metode)>{{ ucfirst($metode) }}</option>@endforeach</select></div></div>
<div class="row"><div class="col-md-6 mb-3"><label class="form-label" for="payment-amount">Jumlah</label><input id="payment-amount" type="number" min="0" max="9999999999.99" step="0.01" name="jumlah" class="form-control @error('jumlah') is-invalid @enderror" value="{{ old('jumlah', $pembayaran->jumlah) }}" @error('jumlah') aria-invalid="true" aria-describedby="payment-amount-error" @enderror required>@error('jumlah')<div id="payment-amount-error" class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="col-md-6 mb-3"><label class="form-label">Status Approval</label><input type="text" class="form-control" value="{{ $pembayaran->exists ? ($pembayaran->status === 'lunas' ? 'Disetujui Pemilik' : 'Menunggu Approval Pemilik') : 'Menunggu Approval Pemilik' }}" readonly><small class="text-body-seco…5933 tokens truncated…njalankan Playwright pada ponsel (390px), tablet (768px) dan desktop (1440px): login, menu mobile, tujuh halaman operasional, form reservasi termasuk error/pemulihan, detail viewer dan CSRF. Laporan, screenshot serta trace kegagalan tersedia pada artifact **browser-smoke-evidence**. Ini simulasi otomatis, bukan UAT manusia atau bukti seluruh UI telah diuji; aset CDN harus termuat agar pemeriksaan layout lulus.

Sebelum membuka traffic pada release directory, jalankan `php artisan kost:check-ui --no-interaction`. Perintah read-only ini mengembalikan exit 1 dan nama berkas bila `public/ui/helpers.js`, `core.js`, atau `LICENSES.txt` hilang, tidak terbaca, atau kosong. Bangun ulang di lingkungan build dengan `npm ci --ignore-scripts && npm run build`, sertakan output `public/ui` dalam paket, lalu ulangi cek. Exit 0 hanya membuktikan kelengkapan tiga berkas, bukan freshness/validitas JS, koneksi CDN, atau kesiapan produksi. Tidak mengakses database, memperbaiki berkas otomatis, maupun menampilkan konfigurasi/secret; smoke browser tetap wajib.

Untuk menjalankan lokal di checkout uji, siapkan `.env` dan key aplikasi, lalu gunakan database **baru** yang khusus untuk browser (jangan memakai database operasional):

```sh
export APP_ENV=testing DB_CONNECTION=sqlite DB_URL=''
export DB_DATABASE="$PWD/storage/framework/testing/browser.sqlite"
export CACHE_STORE=array SESSION_DRIVER=file MAIL_MAILER=array
export WHATSAPP_ENABLED=false WHATSAPP_TOKEN=''
export BROWSER_TEST_PASSWORD=SyntheticBrowserOnly123
mkdir -p storage/framework/testing
touch "$DB_DATABASE"
php artisan migrate --force
php tests/Browser/seed.php
npm ci --ignore-scripts
npx playwright install --with-deps chromium --only-shell
npm run build
npm run test:browser
```

Fixture menolak environment non-testing, koneksi selain SQLite, path selain file khusus tersebut, symlink, password pendek dan database yang sudah berisi pengguna/kamar/penghuni. Server browser selalu localhost port 8115, tidak menggunakan server yang sudah berjalan, dan memakai CSRF normal. Kredensial di atas hanya untuk fixture sintetis; tidak dibuat oleh seeder instalasi aplikasi. Untuk run ulang, gunakan checkout uji baru atau arsipkan database browser lama terlebih dahulu; jangan menghapus database operasional.

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

Job **mysql-concurrency** memakai service MySQL 8.4 ephemeral dengan schema/user khusus. Harness `tests/Concurrency/mysql.php` menolak target lain dan schema tidak kosong, menjalankan migrasi, rollback terbaru, serta rollback/reapply seluruh migrasi **sebelum seeding**, lalu menguji dua proses controller bersamaan: approval idempotent, tagihan overlapping, reservasi bentrok, dan rollback saat ledger gagal. Parent menahan row lock sampai kedua query worker terlihat menunggu; bukan retry berurutan. Jangan jalankan pada database operasional. Kredensial sintetis workflow hanya untuk service CI; tidak ada data/notifikasi penghuni. Ini bukan suite lintas-engine penuh, bukti rollback data legacy aman, atau bukti kesesuaian versi/isolation deployment. Middleware izin/CSRF tetap diuji oleh suite HTTP/browser, bukan controller harness ini.

**Status operasional penuh belum dinyatakan siap.** Rekonsiliasi data historis pembayaran–invoice–buku keuangan dan persaingan transaksi pada database deployment masih memerlukan verifikasi lanjutan. Baca catatan terbaru dalam [`docs/reviews`](docs/reviews) sebelum melanjutkan pekerjaan. Tes tidak menghubungi layanan WhatsApp, memakai data penghuni asli, atau mengubah server/database produksi.

## Tagihan massal pilihan

Buka **Bulk Billing**, pilih bulan, lalu tampilkan daftar. Hanya sewa berstatus aktif dengan masa tinggal yang bertumpang tindih dengan bulan pilihan yang muncul; tanggal checkout tidak termasuk masa tinggal. Semua sewa yang belum memiliki tagihan dicentang awalnya. Hapus centang pada sewa yang hendak ditunda, lalu tekan **Buat Tagihan Pilihan**. Setidaknya satu sewa harus dipilih. Bila pilihan sudah tidak aktif/berubah periode, muat ulang daftar; seluruh batch ditolak tanpa membuat sebagian tagihan.

Pengulangan bulk pada sewa dan bulan yang sama melewati tagihan yang sudah ada, termasuk tagihan manual pada tanggal lain di bulan tersebut. Nominal tetap `biaya_bulanan` penuh, tanpa prorata; Pembayaran baru melalui link sekarang menyimpan cakupan masa sewa dan mencegah bulk pada bulan yang bertumpang tindih. Pembayaran historis tanpa cakupan masih perlu diperiksa karena nominal tidak cukup untuk menebak masa yang telah ditagih. Transaksi membatalkan seluruh batch beserta invoice baru bila penulisan gagal. Hak **Buat Data Sewa** diperlukan.

Pembayaran berstatus **lunas** tidak dapat diubah atau dihapus, termasuk melalui request langsung. Pembayaran yang belum disetujui tetap dapat diedit. Untuk koreksi transaksi lunas, lihat [Pembalikan penuh pembayaran](#pembalikan-penuh-pembayaran); jangan mengubah transaksi yang sudah disetujui.

## Keterlacakan invoice otomatis

Setiap pembayaran baru memiliki paling banyak satu invoice otomatis melalui relasi `payment_id`. Daftar **Invoice** menampilkan nomor pembayaran dan kamar asal agar operator dapat menelusuri nominal serta statusnya. Invoice otomatis mengikuti perubahan pembayaran dan tidak dapat diedit atau dihapus secara terpisah; lakukan koreksi pada pembayaran yang belum disetujui. Menghapus pembayaran belum lunas turut menghapus hanya invoice otomatis miliknya. Invoice manual tetap berdiri sendiri dan tidak ditimpa pembayaran pada penghuni/periode yang sama.

Migrasi `2026_10_07_230500_add_payment_id_to_invoices_table` menghubungkan invoice lama berlabel `AUTO:` hanya jika tepat satu pembayaran cocok berdasarkan penghuni dan periode. Data yang ambigu sengaja dibiarkan sebagai **manual/legacy** agar migrasi tidak menebak kepemilikan transaksi. Periksa invoice manual/legacy setelah deployment dan rekonsiliasikan secara administratif; jangan mengubah histori pembayaran lunas.

## Rekonsiliasi invoice dan bukti pembayaran

Buka **Invoice → Rekonsiliasi Invoice**, lalu pilih kategori: invoice `AUTO:` lama tanpa sumber, pembayaran tanpa invoice, atau invoice yang tidak sesuai dengan pembayaran asal. Perbedaan diperiksa pada penghuni, tanggal periode, nominal (termasuk pecahan), dan status lunas. Invoice terkirim untuk pembayaran pending tetap diperbolehkan. Isi **Bulan rekonsiliasi** lalu tekan **Terapkan** untuk membatasi laporan dan jumlah temuan; **Semua bulan** menghapus batas bulan. Untuk invoice tidak sesuai, kedua bulan terdampak diperiksa: bulan invoice maupun bulan pembayaran asal, sehingga pergeseran periode tidak tersembunyi. Filter dipertahankan saat berpindah kategori atau halaman. Setiap halaman memuat maksimal 25 baris. Hak **Lihat Invoice** diperlukan. Laporan hanya membaca data, tidak menebak relasi invoice legacy, tidak menyentuh invoice manual, dan belum mencocokkan buku Keuangan. Jumlah nol bukan bukti kesiapan seluruh aplikasi.

Pembuatan pembayaran manual dan invoice sekarang dilakukan dalam satu transaksi. Jika invoice gagal ditulis, pembayaran ikut dibatalkan dan berkas baru dibersihkan. Penggantian/penghapusan bukti pembayaran lama dilakukan setelah transaksi berhasil commit; jika database gagal, bukti lama dipertahankan. Pembayaran lunas tetap tidak bisa diedit atau dihapus. Kegagalan penghapusan pada penyimpanan berkas setelah commit masih perlu pemantauan/pembersihan administratif; transaksi database tidak dibatalkan setelah commit.

## Pemasukan otomatis dari pembayaran

Saat pemilik menekan **Approve** pada pembayaran, status pembayaran, invoice, status sewa/kamar, dan satu pemasukan **Sewa Kamar** kini disimpan dalam transaksi database yang sama. Pemasukan menampilkan ID pembayaran asal dan masuk ke ringkasan serta laporan Keuangan berdasarkan tanggal bayar. Pengulangan approval tidak membuat pemasukan kedua. Jika pencatatan Keuangan gagal, seluruh approval dibatalkan sehingga pembayaran dan invoice tidak menjadi lunas sebagian.

Pemasukan otomatis tidak dapat diedit atau dihapus dari menu Keuangan; lakukan penelusuran melalui tombol **Pembayaran**. Transaksi Keuangan manual tetap dapat dibuat dan dikelola seperti sebelumnya, tetapi request manual tidak dapat mengaku sebagai sumber pembayaran. Migrasi menambahkan hubungan unik nullable dan tidak menebak hubungan pencatatan lama. Karena itu pembayaran yang sudah lunas sebelum fitur ini serta pemasukan manual terdahulu tetap perlu direkonsiliasi secara administratif agar tidak dibuat ganda.

## Rekonsiliasi pembayaran–Keuangan dan detail transaksi

Buka **Keuangan → Rekonsiliasi Pembayaran–Keuangan**. Dibutuhkan hak **Lihat Keuangan** dan **Lihat Data Sewa**. Kategori pertama memuat pembayaran lunas tanpa sumber pemasukan terhubung; kategori kedua memuat perbedaan nominal, tanggal, jenis, kategori, atau status asal. Filter mengikuti tanggal bayar, dengan periode sebagai fallback bila tanggal bayar kosong; tanggal pemasukan berbeda turut masuk pada kedua bulan terdampak. Pagination berisi 25 baris dan mempertahankan filter.

Laporan tidak menebak relasi atau melakukan backfill. Untuk pembayaran lunas tanpa sumber, sistem menampilkan kandidat pemasukan manual hanya bila tanggal, nominal, jenis `pemasukan`, dan kategori `Sewa Kamar` persis sama. Operator dengan hak **Ubah Keuangan** dan **Ubah Data Sewa** tetap harus memeriksa deskripsi/bukti, memilih kandidat, serta menulis alasan minimal 10 karakter. Tautan membuat pemasukan immutable, membedakannya dari sumber approval pada detail/CSV, dan menyimpan operator, waktu, alasan, serta histori link/unlink. Tautan yang salah dapat dilepas dengan alasan tanpa menghapus payment atau pemasukan; pemasukan approval otomatis tidak dapat dilepas. Detail Keuangan dapat dibaca tanpa izin edit; bukti tetap melalui endpoint privat, dan tautan pembayaran asal mengikuti izin Data Sewa.

Bukti Keuangan manual kini dipertahankan jika perubahan/penghapusan database gagal. Unggahan pengganti yang gagal disimpan dibersihkan; bukti lama dibersihkan setelah commit berhasil atau masuk antrean retry bila storage gagal.

## Antrean pembersihan file privat

Penghapusan bukti lama dilakukan setelah transaksi database commit. Bila storage mengembalikan gagal atau melempar error, path tidak lagi hilang diam-diam: sistem menyimpan satu antrean unik dengan konteks, jumlah percobaan, error terakhir, dan waktu percobaan. Retry terjadwal berjalan tiap jam maksimal 100 file bila Laravel scheduler aktif. Periksa tanpa mengubah data dengan `php artisan private-files:cleanup --dry-run`; jalankan retry manual dengan `php artisan private-files:cleanup --limit=100`. File yang berhasil dihapus otomatis keluar dari antrean; kegagalan tetap tersimpan dan jumlah percobaan bertambah. Dashboard menampilkan peringatan berisi jumlah antrean, umur antrean tertua, dan percobaan tertinggi tanpa membocorkan path atau pesan error storage. Pastikan worker `php artisan schedule:run`/`schedule:work` benar-benar dikonfigurasi pada deployment.

## Privasi log aktivitas

Log mutasi tetap menyimpan nama route dan URL untuk audit, tetapi nilai parameter route sensitif seperti `token`, `signature`, `secret`, atau `key` diganti dengan `[REDACTED]`. Query string pada request mutasi tidak disimpan karena dapat membawa kredensial atau data sementara. Dengan demikian token pendaftaran/pembayaran publik yang masih aktif tidak ikut tersebar ke pembaca log atau ekspor/backup database. ID objek biasa tetap dapat ditelusuri melalui URL dan nama route.

## Ekspor Keuangan dan pemulihan unggahan

Di **Keuangan**, pilih bulan dan jenis transaksi lalu tekan **Export CSV**. Kosongkan bulan untuk semua periode; Reset mengembalikan bulan saat ini. CSV mengikuti bulan/jenis, termasuk semua halaman. Pencarian teks pada tabel tidak mengubah isi ekspor. Nominal memakai dua desimal tanpa pemisah ribuan; sumber manual, otomatis, rekonsiliasi manual, atau pembalikan dan ID pembayaran dapat ditelusuri. CSV tidak berisi path bukti dan melindungi teks yang dapat dibaca sebagai formula spreadsheet. Hak Lihat Keuangan diperlukan.

Pendaftaran/pembayaran melalui link publik sekarang membersihkan unggahan baru bila database gagal, tanpa menghabiskan token; retry dapat dilakukan setelah masalah penyimpanan selesai. Jika storage menolak berkas, formulir menampilkan pesan pada kolom unggahan dan transaksi tidak dinyatakan sukses. Penggantian bukti manual yang gagal tetap mempertahankan bukti asli.

Bukti pada Data Sewa mengikuti jaminan yang sama: create/update ditolak bila storage gagal, upload baru dibersihkan bila transaksi database rollback, dan bukti lama hanya dihapus setelah update/delete berhasil commit. Jika penghapusan fisik gagal setelah commit, path masuk antrean cleanup dan muncul sebagai ringkasan alarm Dashboard.

Data sewa yang sudah memiliki riwayat pembayaran tidak dapat dihapus. Tombol hapus diganti penanda riwayat pada daftar/detail, endpoint memeriksa ulang di dalam transaksi dengan row lock, dan foreign key payment→sewa memakai `RESTRICT` sebagai pertahanan database. Perlindungan ini mencegah cascade menghilangkan pembayaran, invoice, pemasukan, link, serta bukti transaksi; gunakan status selesai dan pembalikan pembayaran untuk koreksi yang perlu mempertahankan audit.

Penghapusan sewa mengunci kamar sebelum sewa agar sejalan dengan penyelesaian/perpanjangan kamar. Jika kamar berubah saat request penghapusan berlangsung, aksi ditolak tanpa menghapus sewa atau bukti; muat ulang **Data Sewa**, periksa kamar/penghuni terbaru, lalu hapus hanya bila masih dimaksudkan. Ini bukan deteksi semua perubahan form lama; histori pembayaran/reservasi tetap tidak boleh dihapus.

Update sewa juga ditolak bila kamar aktual sudah berubah ke kamar di luar lock request tersebut. Muat ulang **Data Sewa**, periksa kamar/penghuni/periode dan nominal terbaru, lalu ulangi perubahan yang masih diperlukan. Bukti lama tetap disimpan dan unggahan pengganti belum ditulis saat penolakan ini. Perpindahan kamar tetap tersedia; bila kamar aktual sudah sama dengan tujuan yang terkunci, update tetap dapat dilanjutkan. Guard ini bukan versioning seluruh isi form.

Detail Sewa dapat dibuka oleh operator dengan hak **Lihat Data Sewa** untuk menelusuri bukti melalui endpoint privat. Tombol Edit/Hapus hanya dirender bila hak terkait tersedia; request mutasi langsung tetap ditolak middleware. Pengguna tanpa hak lihat tidak dapat membuka detail maupun file bukti.

Menu Penghuni juga memiliki halaman detail read-only terpisah dari form edit. Operator dengan hak **Lihat Data Penghuni** dapat memeriksa identitas, kontak darurat, kendaraan, ringkasan relasi, foto KTP, dan selfie privat tanpa memperoleh aksi mutasi. Halaman sensitif memakai `no-store`; output teks di-escape dan file tetap dilayani lewat pemeriksaan izin.

Detail Reservasi tidak lagi mengalihkan viewer ke form edit. Role **Lihat Data Sewa** dapat memeriksa penghuni, kamar, rencana tinggal, uang muka, status, dan catatan dalam halaman `no-store`; tombol mutasi serta endpoint tetap mengikuti hak update/delete. Reservasi yang sudah dikonversi bersifat immutable dan menaut ke Detail Sewa; sewa hasil konversi juga mempertahankan tautan balik dan tidak dapat dihapus agar jejak operasional tetap utuh.

Detail Kamar juga terpisah dari Edit dan mengikuti hak baca/update/delete. Kamar yang memiliki histori sewa atau reservasi tidak menawarkan tombol hapus dan endpoint memeriksa ulang di dalam transaksi; ini mencegah hilangnya histori operasional karena cascade atau request langsung. Kamar tanpa histori tetap dapat dihapus.

Saat data penghuni yang sudah tidak memiliki relasi sewa/reservasi/invoice dihapus, foto KTP dan selfie privat dibersihkan setelah delete database berhasil commit. Kegagalan database mempertahankan record serta dokumen; kegagalan storage setelah commit memasukkan masing-masing dokumen ke antrean cleanup tanpa membatalkan penghapusan record.

Detail Invoice dan Pembayaran sekarang dapat dibaca dengan hak Lihat masing-masing modul tanpa diarahkan ke halaman Edit. Tautan sumber lintas modul mengikuti izin; transaksi lunas/invoice otomatis tidak menawarkan edit terpisah. Laporan Keuangan HTML/PDF memvalidasi bulan dan tetap tepat saat tanggal sistem 31. Laporan Hunian menghitung kamar unik pada sebagian bulan, mengecualikan tanggal checkout, dan memvalidasi tahun/cakupan; rata-rata harian belum dicakup.

Checkpoint kelanjutan terbaru: [Konversi reservasi menjadi sewa](docs/reviews/2026-10-10-reservation-conversion.md). Baca juga [detail dan histori kamar](docs/reviews/2026-10-10-room-detail-history.md), [detail reservasi read-only](docs/reviews/2026-10-10-reservation-detail-access.md), [detail penghuni read-only](docs/reviews/2026-10-10-resident-detail-access.md), [detail sewa read-only](docs/reviews/2026-10-10-lease-detail-access.md), dan [perlindungan histori sewa](docs/reviews/2026-10-10-lease-history-preservation.md) sebelum mengulang backlog.

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

Bila tautan pemasukan manual sudah dilepas sebelum pembalikan diproses, pembalikan ditolak tanpa menulis pengeluaran. Cocokkan ulang sumber yang benar melalui Rekonsiliasi, tautkan kembali dengan alasan, lalu ulangi pembalikan. Jangan membuat pemasukan duplikat untuk melewati pesan sumber tidak ditemukan. CI MySQL memeriksa kedua urutan reverse/unlink serta dua pembalikan bersamaan; hasil ini tidak menggantikan validasi database deployment.

Jika server gagal menyimpan pembalikan, periksa dahulu Detail Pembayaran dan buku Keuangan untuk memastikan status terakhir. Setelah gangguan database ditangani, ulangi tindakan pembalikan yang sama; jangan membuat pengeluaran manual sebagai jalan pintas. Pengujian MySQL menyisipkan kegagalan write kedua untuk memeriksa rollback offset dan retry tanpa duplikasi; ini bukan bukti bahwa semua kegagalan infrastruktur sudah tercakup.

Pemasukan yang menjadi **asal pembalikan** dikunci untuk edit/hapus, lepas tautan rekonsiliasi, dan pemakaian ulang pada pembayaran lain. Detail Keuangan menjelaskan proteksi ini dan tidak menawarkan tombol yang dilarang. Koreksi tautan manual yang belum menjadi sumber pembalikan tetap diperbolehkan dengan alasan/audit. Record historis yang sudah terlepas juga dilindungi lewat relasi pembalikan, ditandai pada daftar/detail/CSV, tetapi tidak ditautkan ulang atau diperbaiki nominalnya secara otomatis; lakukan pemeriksaan histori terpisah agar sumber tidak ditebak.
