@php
$pageTitle = 'Bantuan';
@endphp
@extends('layouts.app')

@section('content')
<style>
    .help-step-number {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #eaf2ff;
        color: #1f5ca8;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .help-command {
        display: inline-block;
        border: 1px solid #d6e4fb;
        background: #f3f8ff;
        color: #2559a8;
        border-radius: .5rem;
        padding: .3rem .55rem;
        font-size: .78rem;
        margin-top: .35rem;
    }

    .help-check li {
        margin-bottom: .45rem;
    }

    .help-check li:last-child {
        margin-bottom: 0;
    }

</style>

<div class="page-head d-flex align-items-center gap-3">
    <div class="page-head-icon"><i class="bx bx-help-circle"></i></div>
    <div>
        <h2>Pusat Bantuan Aplikasi Kost San</h2>
        <p>Ikuti urutan di bawah untuk menjalankan aplikasi, memahami flow operasional, dan memastikan data penting tidak terlewat.</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h5 class="mb-0">1) Menjalankan Aplikasi</h5>
            <small class="text-body-secondary">Jalankan dari folder project</small>
        </div>

        <div class="row g-3">
            <div class="col-12 col-md-6 col-lg-3">
                <div class="border rounded p-3 h-100">
                    <div class="help-step-number mb-2">1</div>
                    <strong class="d-block">Install Dependency</strong>
                    <small class="text-body-secondary d-block">Backend dan frontend wajib terpasang.</small>
                    <span class="help-command">composer install</span>
                    <span class="help-command">npm install</span>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="border rounded p-3 h-100">
                    <div class="help-step-number mb-2">2</div>
                    <strong class="d-block">Konfigurasi Aplikasi</strong>
                    <small class="text-body-secondary d-block">Siapkan env dan app key.</small>
                    <span class="help-command">copy .env.example .env</span>
                    <span class="help-command">php artisan key:generate</span>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="border rounded p-3 h-100">
                    <div class="help-step-number mb-2">3</div>
                    <strong class="d-block">Database</strong>
                    <small class="text-body-secondary d-block">Buat struktur tabel terbaru.</small>
                    <span class="help-command">php artisan migrate</span>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="border rounded p-3 h-100">
                    <div class="help-step-number mb-2">4</div>
                    <strong class="d-block">Jalankan Service</strong>
                    <small class="text-body-secondary d-block">Aktifkan web, assets, dan scheduler.</small>
                    <span class="help-command">php artisan serve</span>
                    <span class="help-command">npm run dev</span>
                    <span class="help-command">php artisan schedule:work</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-3">2) Flow Penggunaan Aplikasi</h5>

        <div class="d-flex flex-column gap-3">
            <div class="border rounded p-3">
                <div class="d-flex align-items-start gap-3">
                    <span class="help-step-number">1</span>
                    <div>
                        <strong class="d-block">Pengaturan > Profil</strong>
                        <small class="text-body-secondary">Isi identitas kost, kontak, dan deskripsi agar sistem punya data dasar operasional.</small>
                    </div>
                </div>
            </div>

            <div class="border rounded p-3">
                <div class="d-flex align-items-start gap-3">
                    <span class="help-step-number">2</span>
                    <div>
                        <strong class="d-block">Master Data > Kamar / Data Lantai</strong>
                        <small class="text-body-secondary">Tambah lantai, tambah kamar, lalu atur status kamar untuk memudahkan mapping sewa.</small>
                    </div>
                </div>
            </div>

            <div class="border rounded p-3">
                <div class="d-flex align-items-start gap-3">
                    <span class="help-step-number">3</span>
                    <div>
                        <strong class="d-block">Master Data > Penghuni</strong>
                        <small class="text-body-secondary">Input penghuni langsung atau gunakan link pendaftaran untuk self-entry penghuni.</small>
                    </div>
                </div>
            </div>

            <div class="border rounded p-3">
                <div class="d-flex align-items-start gap-3">
                    <span class="help-step-number">4</span>
                    <div>
                        <strong class="d-block">Manajemen Sewa > Data Sewa</strong>
                        <small class="text-body-secondary">Buat kontrak sewa berdasarkan penghuni dan kamar. Status kamar akan mengikuti status sewa.</small>
                    </div>
                </div>
            </div>

            <div class="border rounded p-3">
                <div class="d-flex align-items-start gap-3">
                    <span class="help-step-number">5</span>
                    <div>
                        <strong class="d-block">Pembayaran</strong>
                        <small class="text-body-secondary">Input pembayaran, lalu lakukan approve pemilik agar pembayaran tercatat final (lunas).</small>
                    </div>
                </div>
            </div>

            <div class="border rounded p-3">
                <div class="d-flex align-items-start gap-3">
                    <span class="help-step-number">6</span>
                    <div>
                        <strong class="d-block">Keuangan, Invoice, dan Laporan</strong>
                        <small class="text-body-secondary">Catat pemasukan/pengeluaran, review invoice, dan cek laporan bulanan untuk monitoring bisnis kost.</small>
                    </div>
                </div>
            </div>

            <div class="border rounded p-3">
                <div class="d-flex align-items-start gap-3">
                    <span class="help-step-number">7</span>
                    <div>
                        <strong class="d-block">Pengaturan > Notifikasi</strong>
                        <small class="text-body-secondary">Aktifkan WA, pilih jenis notifikasi, dan sesuaikan template agar pesan otomatis sesuai kebutuhan.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-3">3) Flow Pendaftaran Penghuni sampai Pembayaran &amp; Verifikasi</h5>
        <div class="alert alert-primary mb-3" role="alert">
            Flow ini dipakai saat penghuni mendaftar mandiri lewat link yang dibagikan admin (tanpa perlu login).
        </div>

        <div class="d-flex flex-column gap-3">
            <div class="border rounded p-3">
                <div class="d-flex align-items-start gap-3">
                    <span class="help-step-number">1</span>
                    <div>
                        <strong class="d-block">Admin membuat link pendaftaran</strong>
                        <small class="text-body-secondary">Dari Master Data &gt; Penghuni (atau Sewa Kamar untuk kamar tertentu), klik tombol buat link pendaftaran. Link berlaku 7 hari dan hanya bisa dipakai sekali.</small>
                    </div>
                </div>
            </div>

            <div class="border rounded p-3">
                <div class="d-flex align-items-start gap-3">
                    <span class="help-step-number">2</span>
                    <div>
                        <strong class="d-block">Penghuni mengisi form pendaftaran mandiri</strong>
                        <small class="text-body-secondary">Calon penghuni membuka link dan mengisi data diri, kontak darurat, kendaraan, memilih durasi sewa (1/3/6/12 bulan), lalu upload foto KTP dan foto selfie. Semua field wajib divalidasi sebelum bisa submit.</small>
                    </div>
                </div>
            </div>

            <div class="border rounded p-3">
                <div class="d-flex align-items-start gap-3">
                    <span class="help-step-number">3</span>
                    <div>
                        <strong class="d-block">Sistem otomatis membuat data Penghuni dan Sewa</strong>
                        <small class="text-body-secondary">Setelah form disubmit, sistem membuat data penghuni, membuat kontrak sewa (status "menunggak") dengan harga dan tanggal keluar mengikuti durasi yang dipilih, lalu link pendaftaran ditandai sudah dipakai sehingga tidak bisa diakses ulang.</small>
                    </div>
                </div>
            </div>

            <div class="border rounded p-3">
                <div class="d-flex align-items-start gap-3">
                    <span class="help-step-number">4</span>
                    <div>
                        <strong class="d-block">Sistem membuat link pembayaran otomatis</strong>
                        <small class="text-body-secondary">Bersamaan dengan sewa dibuat, sistem membuat link pembayaran (berlaku 7 hari, sekali pakai) dan menampilkannya ke penghuni, sekaligus mengirim notifikasi WhatsApp berisi link tersebut jika fitur notifikasi aktif.</small>
                    </div>
                </div>
            </div>

            <div class="border rounded p-3">
                <div class="d-flex align-items-start gap-3">
                    <span class="help-step-number">5</span>
                    <div>
                        <strong class="d-block">Penghuni membayar lewat link pembayaran</strong>
                        <small class="text-body-secondary">Penghuni membuka link pembayaran, sistem menampilkan rincian tagihan (harga per bulan x durasi, dibulatkan ke kelipatan Rp 50.000). Penghuni memilih metode (cash/transfer), boleh melampirkan bukti pembayaran, lalu submit.</small>
                    </div>
                </div>
            </div>

            <div class="border rounded p-3">
                <div class="d-flex align-items-start gap-3">
                    <span class="help-step-number">6</span>
                    <div>
                        <strong class="d-block">Pembayaran masuk dengan status "Belum Lunas"</strong>
                        <small class="text-body-secondary">Data pembayaran otomatis tercatat di menu Pembayaran dengan status "Belum Lunas" (menunggu approval admin/pemilik), dan link pembayaran ditandai sudah dipakai.</small>
                    </div>
                </div>
            </div>

            <div class="border rounded p-3">
                <div class="d-flex align-items-start gap-3">
                    <span class="help-step-number">7</span>
                    <div>
                        <strong class="d-block">Admin/pemilik verifikasi dan approve pembayaran</strong>
                        <small class="text-body-secondary">Admin membuka menu Pembayaran, cek bukti transfer/keterangan, lalu klik Approve untuk mengubah status jadi "Lunas". Setelah lunas, pemasukan sewa otomatis tercatat di buku Keuangan, status sewa penghuni ikut jadi aktif, dan notifikasi WhatsApp konfirmasi bisa terkirim (jika diaktifkan).</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="mb-3">4) Data yang Wajib Diisi</h5>

        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <div class="border rounded p-3 h-100">
                    <h6 class="mb-2">A. Profil Kost</h6>
                    <ul class="help-check mb-0">
                        <li>Nama kost</li>
                        <li>Pemilik (disarankan)</li>
                        <li>Telepon/WhatsApp pengelola (disarankan)</li>
                        <li>Alamat kost (disarankan)</li>
                    </ul>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="border rounded p-3 h-100">
                    <h6 class="mb-2">B. Data Kamar</h6>
                    <ul class="help-check mb-0">
                        <li>Nomor kamar</li>
                        <li>Harga sewa bulanan</li>
                        <li>Status kamar (tersedia/terisi/perbaikan)</li>
                    </ul>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="border rounded p-3 h-100">
                    <h6 class="mb-2">C. Data Penghuni</h6>
                    <ul class="help-check mb-0">
                        <li>Nama lengkap</li>
                        <li>Nomor telepon aktif</li>
                        <li>Identitas dasar (KTP dan data pribadi lain sesuai form)</li>
                    </ul>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="border rounded p-3 h-100">
                    <h6 class="mb-2">D. Data Sewa</h6>
                    <ul class="help-check mb-0">
                        <li>Penghuni</li>
                        <li>Kamar</li>
                        <li>Tanggal masuk dan tanggal keluar</li>
                        <li>Biaya bulanan</li>
                    </ul>
                </div>
            </div>

            <div class="col-12">
                <div class="border rounded p-3 h-100">
                    <h6 class="mb-2">E. Data Pembayaran</h6>
                    <ul class="help-check mb-0">
                        <li>Data sewa terkait</li>
                        <li>Periode bayar</li>
                        <li>Metode bayar</li>
                        <li>Jumlah bayar</li>
                        <li>Bukti pembayaran (disarankan)</li>
                        <li>Approval pemilik untuk finalisasi</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="mb-3">5) Catatan Notifikasi WhatsApp</h5>
        <div class="alert alert-primary mb-3" role="alert">
            Pastikan token, base URL, dan scheduler aktif agar pesan otomatis benar-benar terkirim.
        </div>
        <ul class="help-check mb-0">
            <li>Isi Token API dan Base URL provider WhatsApp dengan benar.</li>
            <li>Pilih jenis notifikasi yang ingin diaktifkan.</li>
            <li>Untuk reminder masa sewa habis, atur H-berapa hari sebelum tanggal keluar.</li>
            <li>Jika opsi kirim berulang diaktifkan, notifikasi akan dikirim setiap hari sampai pembayaran lunas.</li>
            <li>Jalankan scheduler: <code>php artisan schedule:work</code>.</li>
        </ul>
    </div>
</div>
@endsection
