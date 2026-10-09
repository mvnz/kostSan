# Checkpoint deduplikasi tagihan manual — 9 Oktober 2026

## Source dan temuan

Pekerjaan dilanjutkan pada head draft PR #4 `3758f92a4e552644adc4d96a1705334ec26e9f12`, berbasis main terbaru yang diperiksa `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`. PR lain dan issue non-PR terbuka tidak ada; CI head awal sukses. Tidak ada `AGENTS.md`.

Reproduksi controller pada data sintetis membuktikan dua POST pembayaran manual dengan sewa dan bulan sama sama-sama berhasil dan membuat dua payment/invoice. Pembayaran manual juga dapat tumpang tindih dengan snapshot pembayaran link multi-bulan. Pemeriksaan duplikasi bulk/link yang sudah ada belum dipakai pada create manual, dan unggahan dilakukan sebelum keputusan duplikasi.

## Perubahan dan kriteria penerimaan

Pembayaran manual baru menyimpan snapshot cakupan satu bulan. Create mengunci baris sewa, memeriksa seluruh cakupan pembayaran pada transaksi yang sama, lalu baru menyimpan bukti, payment, dan invoice. Benturan manual/bulk/link ditolak default; berkas yang ditolak tidak tersisa. Edit pembayaran legacy yang memindahkan sewa/periode memakai pemeriksaan sama dan tidak menjadi jalur bypass. Urutan lock edit diselaraskan dengan approval: payment lalu lease.

Cicilan/tagihan tambahan yang disengaja tetap didukung melalui checkbox pengecualian dan alasan wajib 10–500 karakter. Alasan disimpan pada payment dan terlihat di detail sebagai jejak audit. Pengecualian ditolak bila tidak ada benturan agar metadata tidak disalahgunakan. Setiap pengecualian menghasilkan payment/invoice independen; fitur ini tidak menghitung saldo cicilan otomatis.

Kriteria penerimaan: duplikasi default ditolak dengan pesan pemulihan; cakupan bulanan tersimpan; benturan snapshot multi-bulan terdeteksi; alasan pendek/tanpa alasan ditolak; pengecualian valid tercatat dan di-escape; upload ditolak dibersihkan; edit legacy tidak melewati aturan; invoice tetap atomik dan satu per payment.

## Validasi run ini

| Kategori | Status | Bukti dan batasan |
|---|---|---|
| Functional | LULUS | PHPUnit mencakup create valid, duplikat, batas alasan, override tanpa benturan, benturan link, edit legacy, coverage dan invoice. HTTP nyata mengulang reject default → alasan pendek → override valid. |
| User flow & transaction | LULUS lokal | HTTP session/CSRF menjalankan kamar–penghuni–reservasi–sewa–pembayaran–approval–invoice–ledger–rekonsiliasi–reversal, lalu alur overlap baru. Rollback upload/invoice diuji otomatis. Persaingan riil pada DB deployment belum diuji. |
| Responsive | BELUM DIUJI | Build berhasil, tetapi browser executable/viewport interaktif tidak tersedia. Build bukan bukti responsive. |
| Bug & regression | LULUS | Reproduksi sebelum perubahan gagal 3 skenario; suite akhir 133 tes/941 asersi, frontend 4/4, route/view compile dan build lulus. |
| Retest | LULUS | Duplikat sekarang 422, hanya satu payment/invoice dan tanpa file; override valid tepat menambah satu payment/invoice dan alasan. Suite serta smoke diulang setelah perbaikan lock. |
| User testing | BELUM DIUJI | Simulasi agen/operator lewat HTTP lulus; UAT manusia nyata belum dilakukan. |
| Security | LULUS dalam lingkup uji | Auth redirect, CSRF 419, izin suite, validasi boolean/date/amount/reason/file, XSS escaping alasan, upload privat/cleanup, audit Composer/npm dan scan secret tracked diperiksa. Uji serangan produksi tidak dilakukan. |

Migrasi database SQLite baru lulus maju → rollback migrasi terakhir → maju. Composer validate/audit dan npm audit menemukan nol advisory. HTTP memakai data sintetis dan tidak mengirim WhatsApp.

## Kelanjutan

Status tetap **belum siap operasional penuh** sampai responsive browser, UAT manusia, dan concurrency pada jenis database deployment diverifikasi. Prioritas independen berikut: pencocokan pemasukan manual historis dengan bukti tanpa backfill spekulatif; saldo cicilan/partial payment jika definisi bisnis tersedia; durable cleanup untuk kegagalan hapus pascacommit. Draft PR #4 tetap satu-satunya PR kerja dan jangan di-merge sebelum gap wajib tersebut selesai.

Skenario UAT: admin membuat tagihan manual bulan kosong; mencoba bulan sama tanpa pengecualian; membaca pesan; membuat cicilan kedua dengan alasan; memastikan dua invoice/nominal dan alasan detail; mencoba alasan pendek; mengedit tagihan legacy; menjalankan sebagai role operator yang sesuai pada ponsel, tablet, dan desktop. Verifikasi bahwa operator memahami pengecualian tidak menghitung sisa saldo.
