# Review keterlacakan invoice — 7 Oktober 2026

## Baseline

- Source terbaru yang diperiksa: `main` pada `126bc1e06f3124bc513f25555cbb09d59c909144`, tree `a0c48004d40320c68f2b4e647868f98d90e07838`.
- CI pascamerge baseline: https://github.com/mvnz/kostSan/actions/runs/37504023472, seluruh langkah lulus.
- Tidak ada `AGENTS.md`, PR terbuka, issue terbuka, atau ruleset. Review terdahulu dibaca; pekerjaan ini mengambil prioritas hubungan pembayaran–invoice dan tidak mengulang perubahan PR #1/#2.

## Temuan yang direproduksi

1. Dua pembayaran berbeda untuk penghuni dan periode yang sama menghasilkan hanya satu invoice. Pembayaran kedua menimpa nominal invoice pertama karena sinkronisasi mencari invoice berdasarkan `penghuni_id + periode`.
2. Invoice manual untuk penghuni/periode yang sama ditimpa saat pembayaran dibuat.
3. Penghapusan pembayaran belum lunas meninggalkan invoice otomatis tanpa sumber transaksi.
4. Tampilan menguji status `dikirim`, padahal enum menyimpan `terkirim`, sehingga badge status kirim tidak pernah menggunakan tampilan yang semestinya.

Kelima tes baru dijalankan pada baseline: satu lulus, dua gagal, dan dua error karena relasi `payment_id` belum tersedia. Ini menjadi bukti reproduksi sebelum implementasi.

## Perbaikan dan peningkatan operasional

- Kolom `invoices.payment_id` nullable, unik, dan foreign key ke pembayaran dengan cascade delete. Nullable menjaga invoice manual dan data lama; unique menegakkan satu invoice per pembayaran pada database.
- Sinkronisasi invoice memakai identitas pembayaran, bukan penghuni+periode. Dua sewa penghuni pada periode sama tidak lagi saling menimpa.
- Invoice manual tidak disentuh sinkronisasi pembayaran. Invoice otomatis tidak dapat diedit atau dihapus secara terpisah melalui controller maupun tombol UI.
- Daftar invoice menampilkan `Pembayaran #ID` dan nomor kamar asal, sedangkan invoice tanpa relasi diberi label manual/legacy. Badge enum `terkirim` diperbaiki.
- Penghapusan pembayaran belum disetujui menghapus invoice otomatis terkait melalui foreign key, tetapi mempertahankan invoice manual lain.
- Migrasi menghubungkan invoice lama `AUTO:` hanya bila tepat satu pembayaran cocok. Kasus ambigu dibiarkan tanpa relasi untuk rekonsiliasi manusia, bukan ditebak.

Kriteria penerimaan: pembayaran berbeda selalu memperoleh invoice berbeda; invoice manual tidak berubah; update pembayaran memperbarui invoice miliknya; invoice otomatis tidak dapat dimutasi langsung; penghapusan pembayaran pending tidak meninggalkan invoice yatim; asal pembayaran terlihat di daftar; data lama ambigu tidak diubah secara spekulatif.

## Validasi

- PHPUnit akhir: **54 tes / 271 assertions**, lulus dengan `--fail-on-warning --fail-on-risky`. Lima tes baru mencakup dua pembayaran pada penghuni/periode sama, perlindungan invoice manual, cascade invoice otomatis, larangan mutasi langsung, serta informasi sumber/status pada daftar.
- Migrasi seluruh skema pada SQLite baru, rollback migrasi invoice, lalu migrasi ulang: lulus. Tidak ada migrasi atau data operasional yang disentuh.
- `composer validate --strict` dan `composer check-platform-reqs`: lulus. Audit lokal tidak dapat mengambil endpoint advisori Packagist karena timeout jaringan; status keamanan dependency tidak diklaim dari percobaan itu dan harus diverifikasi oleh CI PR dengan akses jaringan.
- Empat tes frontend dan build Vite: lulus. Peringatan `fontaine` opsional tidak memblokir build. Route cache dan view cache berhasil dikompilasi.
- HTTP server lokal dengan database sintetis: login memakai session/CSRF, dua pembayaran untuk penghuni/periode sama tampil sebagai dua invoice dengan ID pembayaran dan kamar berbeda; halaman pembayaran, sewa, keuangan, laporan keuangan, dan laporan hunian berhasil dimuat.
- Seluruh data tes sintetis. Tidak ada akses database/server operasional atau pengiriman notifikasi penghuni.

## Batasan dan prioritas berikutnya

**Belum siap operasional penuh.** Perubahan ini memperbaiki identitas invoice ke depan dan kasus legacy yang tidak ambigu, tetapi deployment harus mencadangkan database dan meninjau baris manual/legacy setelah migrasi.

1. Kasus invoice lama ambigu memerlukan laporan rekonsiliasi khusus; migrasi sengaja tidak memilih salah satu pembayaran.
2. Approval pembayaran belum otomatis mencatat pemasukan pada buku Keuangan. Hubungan pembayaran–keuangan dan reversal/refund masih perlu dirancang dengan identitas sumber yang eksplisit.
3. Deduplikasi lintas link pembayaran multi-bulan dan bulk per bulan belum memiliki penanda cakupan bersama.
4. Foreign key dan transaksi bersamaan perlu diuji pada jenis database staging/deployment; SQLite hanya membuktikan perilaku lokal.
5. Siklus berkas bukti pembayaran ketika update database gagal masih perlu perbaikan pascacommit/pembersihan.

Lanjutkan dari `main`/PR terbaru dan hindari menduplikasi branch yang belum merge.
