# Checkpoint: metrik reservasi aktif Dashboard

Tanggal: 10 Oktober 2026  
Basis awal: `61f02b8120e12276dac863b8261aa967be4097c4`

## Temuan

Kartu **Reservasi — Calon penghuni** memakai `Reservasi::count()`, sehingga seluruh histori dibatalkan, dikonversi, kedaluwarsa, dan record terkonfirmasi lama ikut dihitung sebagai calon penghuni aktif. Angka makin menyimpang ketika lifecycle konversi/kedaluwarsa mulai dipakai. Kartu **Terisi** juga memakai `total kamar - kamar tersedia`, sehingga kamar berstatus `perbaikan` salah dihitung sebagai sedang dihuni.

## Perbaikan

Dashboard sekarang menampilkan **Reservasi Aktif — Menunggu / dikonfirmasi**. Query hanya menghitung kedua status operasional tersebut dengan tanggal keluar kosong atau lebih besar dari hari berjalan. Hari checkout eksklusif tidak lagi dihitung. Histori tetap tersimpan di daftar/detail.

Jumlah **Kamar Terisi** sekarang dihitung langsung dengan `status = terisi`; status `perbaikan` tetap terpisah dan tidak menggelembungkan hunian.

Kriteria penerimaan: future confirmed + open-ended waiting dihitung; canceled, converted, expired, serta confirmed dengan checkout hari ini tidak dihitung. Dari masing-masing satu kamar tersedia, terisi, dan perbaikan, metrik menunjukkan total 3, tersedia 1, terisi 1.

## Pengujian

`DashboardReservationMetricTest` memakai enam reservasi sintetis lintas status/batas tanggal dan membuktikan hasil tepat dua, serta tiga kamar sintetis lintas status untuk membuktikan kamar perbaikan tidak dihitung terisi. Regresi penuh dijalankan sebelum commit/publikasi.

| Kategori | Status | Bukti/batasan |
|---|---|---|
| Functional | LULUS | Enam kombinasi status/periode dan label Dashboard diuji. |
| User flow & transaction | LULUS lokal | Dashboard read-only tidak memutasi data; lifecycle sumber diuji pada prioritas sebelumnya. |
| Responsive | BELUM DIUJI | Browser viewport tidak tersedia; build bukan bukti responsive. |
| Bug & regression | LULUS | Suite backend penuh dijalankan. |
| Retest | LULUS | Reproduksi total histori diganti filter aktif dan diverifikasi. |
| User testing | BELUM DIUJI | Simulasi HTTP agen lulus; UAT manusia belum dilakukan. |
| Security | LULUS dalam lingkup | Query agregat tidak mengekspos atribut penghuni; regresi auth tetap dijalankan. |

## Berikutnya

Audit notifikasi ulang tahun penghuni menunggak dan kualitas daftar reservasi (filter status); responsive/UAT dan concurrency DB deployment tetap belum terverifikasi.
