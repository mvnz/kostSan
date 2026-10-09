# Checkpoint detail penghuni read-only — 10 Oktober 2026

## Source dan temuan

Prioritas dimulai dari draft PR #4 head `8ad13019fa760a845d1611da4c2647e8685db776`, exact tree `7758445e815a03863ef0ab4a109403bd5e2cc38a`.

Route Detail Penghuni membutuhkan hak view, tetapi controller langsung mengalihkan ke form Edit yang membutuhkan update. Akibatnya role read-only diarahkan kembali ke landing dan tidak dapat memeriksa data/dokumen yang secara eksplisit boleh dilihat.

## Perubahan dan manfaat

Detail Penghuni kini halaman read-only tersendiri dengan header `no-store, private`. Halaman menampilkan data operasional, dokumen KTP/selfie melalui endpoint privat, dan jumlah relasi tanpa membuka data lintas modul. Aksi Edit/Hapus mengikuti permission masing-masing. Teks penghuni tetap di-escape.

Kriteria penerimaan: viewer membuka detail dan dua dokumen; NIK terlihat tetapi payload XSS tidak dieksekusi/render mentah; URL edit/form delete tidak ada; request edit/delete langsung ditolak; role tanpa view tidak dapat membuka detail/file.

## Validasi

| Kategori | Status | Bukti dan batasan |
|---|---|---|
| Functional | LULUS | Dua regresi role memeriksa detail, field, dokumen, no-store, XSS escaping, mutation denial, redirect dan file 403. |
| User flow & transaction | LULUS lokal | Viewer → daftar → detail → KTP/selfie privat dapat diselesaikan tanpa hak edit. Tidak ada mutasi bisnis. |
| Responsive | BELUM DIUJI | Layout memakai grid `col-12 col-lg-*`, wrap tombol, dan text-break; browser ponsel/tablet/desktop tidak tersedia. |
| Bug & regression | LULUS | Suite final, frontend/build, cache dan audit dicatat pada commit/PR. |
| Retest | LULUS | Redirect loop/denial dari Detail ke Edit direproduksi dari kode lama; viewer kini mendapat halaman 200 yang sesuai. |
| User testing | BELUM DIUJI | Simulasi agen viewer menilai informasi terkelompok dan dokumen dapat ditemukan; UAT operator manusia belum dilakukan. |
| Security | LULUS dalam cakupan | Least privilege, mutation 403, private-file 403, no-store dan XSS escaping diuji. Bukan pentest penuh. |

Data/dokumen seluruhnya sintetis. Status tetap **belum siap operasional penuh** sampai responsive browser, UAT manusia, dan concurrency/database/storage deployment representatif tervalidasi.

## Kelanjutan

Audit detail Kamar/Reservasi dan navigasi role read-only tersisa; kemudian prioritaskan browser responsive/UAT staging. Partial payment/refund tetap menunggu definisi bisnis.
