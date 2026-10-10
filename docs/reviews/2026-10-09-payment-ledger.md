# Checkpoint pembayaran–Keuangan — 9 Oktober 2026

## Baseline dan koordinasi

GitHub `main` diperiksa langsung dan fetch ulang pada `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`. Satu pekerjaan terbuka adalah draft PR #4 dengan head awal pelaksanaan `22dc7f8e799abd93729e1de28a6af2550087bfb3`; CI 37806366498 sukses. Tidak ada issue non-PR terbuka atau `AGENTS.md`. Pelaksanaan melanjutkan PR/branch tersebut dan membaca README, konfigurasi, riwayat, serta seluruh checkpoint sebelumnya. Tidak ada pekerjaan paralel lain yang terdeteksi pada scope ini.

## Temuan dan perbaikan

Tes reproduksi membuktikan approval pembayaran mengubah pembayaran/invoice menjadi lunas tetapi tidak membuat pemasukan Keuangan. Akibatnya saldo dan laporan Keuangan dapat lebih rendah daripada pembayaran sah. Empat skenario awal menghasilkan tiga gagal dan satu error pada baseline fitur.

Migrasi baru menambahkan `keuangans.payment_id` nullable, unik, dan foreign key. Nullable mempertahankan transaksi manual/lama; unique menegakkan paling banyak satu pemasukan per pembayaran. Approval kini membuat pemasukan **Sewa Kamar** dalam transaksi database yang sama dengan status pembayaran, invoice, sewa, dan kamar. Tanggal memakai tanggal bayar; deskripsi memuat ID pembayaran, penghuni, kamar, dan periode. Pengulangan approval tidak menggandakan pemasukan. Injeksi kegagalan INSERT Keuangan membuktikan seluruh approval di-rollback.

Pemasukan otomatis diberi label dan tautan sumber bila pengguna juga berhak melihat pembayaran. Pengguna Keuangan tanpa izin pembayaran hanya melihat label sumber, bukan tautan yang berakhir ditolak. Pemasukan otomatis tidak dapat diedit/dihapus melalui controller atau tombol UI; transaksi manual tetap dapat dikelola. Payload transaksi manual yang menyisipkan `payment_id` diabaikan. Migrasi tidak menebak pemasukan lama agar tidak menduplikasi pencatatan manual historis.

Kriteria penerimaan: approval membuat tepat satu pemasukan yang masuk ke laporan bulanan; retry idempotent; kegagalan Keuangan membatalkan seluruh approval; sumber dapat ditelusuri sesuai izin; sumber otomatis immutable; transaksi manual tetap tersedia; hubungan unik ditegakkan database; migrasi fresh/rollback/re-migrate lulus.

## Tujuh kategori pengujian

| Kategori | Status | Bukti dan batas |
|---|---|---|
| Functional | LULUS | Pemasukan otomatis, tanggal/nominal/deskripsi, ringkasan/laporan bulanan, manual entry, constraint unik, proteksi mutasi dan tampilan sumber diuji. |
| User flow & transaction | LULUS dalam lingkungan lokal | HTTP login/CSRF → approval → Keuangan → laporan bulan → retry; kegagalan ledger membatalkan pembayaran, invoice, sewa, kamar. Concurrency pada database deployment BELUM DIUJI. |
| Responsive | BELUM DIUJI | Chromium tidak tersedia; percobaan unduh browser pada pelaksanaan sebelumnya gagal. Build bukan bukti responsive. Perlu 375×812, 768×1024, dan 1440×900. |
| Bug & regression | LULUS | Full PHPUnit, frontend tests/build, fresh migration, rollback/re-migrate, route/view compilation dan diff check dijalankan pada source final. |
| Retest | LULUS | Reproduksi tanpa ledger kini lulus; masalah rollback indeks SQLite yang ditemukan selama implementasi diperbaiki lalu full suite lulus. |
| User testing | BELUM DIUJI | Simulasi operator/admin melalui HTTP lulus; evaluasi visual/usability dan UAT pengguna nyata belum dilakukan. |
| Security | LULUS dalam cakupan tes | Auth/role denial, CSRF HTTP, spoof `payment_id`, immutability sumber, izin tautan lintas modul, suite upload/token/injection/traversal serta `composer audit --locked`. Bukan pentest penuh. |

Semua data sintetis pada SQLite lokal. Tidak ada database/server produksi atau pesan penghuni yang disentuh. SHA/tree final dan CI dicantumkan pada commit/PR yang memuat checkpoint ini.

## Batasan dan kelanjutan

Status tetap **belum siap operasional penuh**. Pembayaran lunas sebelum migrasi dan transaksi Keuangan lama belum dihubungkan karena pencocokan otomatis berisiko membuat pemasukan ganda. Siapkan laporan rekonsiliasi read-only sebelum menawarkan backfill. Reversal/refund belum tersedia. Lanjutkan juga deduplikasi pembayaran multi-bulan, concurrency pada jenis database deployment, responsive/usability, serta lifecycle berkas Keuangan. Jangan membuat PR duplikat; lanjutkan draft #4 sampai validasi yang diwajibkan selesai.

Skenario UAT berikutnya: approve satu pembayaran sintetis; pastikan pesan sukses menyebut buku Keuangan; buka Keuangan dan laporan bulan; telusuri tombol Pembayaran dengan role gabungan; pastikan role Keuangan-only memahami label tanpa tautan; coba edit/delete sumber otomatis dan verifikasi pesan pemulihan; ulangi pada tiga viewport. UAT nyata hanya dinyatakan selesai dengan partisipasi dan bukti manusia.
