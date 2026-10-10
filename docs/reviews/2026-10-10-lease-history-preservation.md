# Checkpoint perlindungan histori sewa — 10 Oktober 2026

## Source dan temuan

Prioritas dimulai dari draft PR #4 head `614278fb9834e767a1fee536f4c9cb0655301881`, exact tree `e41ae76646c6a863e1215c76930530b46c03f814`.

Foreign key pembayaran memakai cascade delete dari sewa; invoice dan pemasukan otomatis juga cascade dari pembayaran. Endpoint delete sewa tidak memiliki guard. Reproduksi pada pembayaran lunas membuktikan permintaan delete tidak ditolak dan berpotensi menghilangkan payment, invoice, ledger, link, serta jejak bukti secara berantai.

## Perubahan dan manfaat

Delete sewa kini mengunci row target dan memeriksa keberadaan payment di dalam transaksi. Jika ada histori, tidak ada mutasi dan operator menerima pesan bahwa record wajib dipertahankan untuk audit. Daftar serta detail tidak menawarkan tombol delete; sebagai gantinya terlihat penanda **Riwayat pembayaran tersimpan**. Migrasi mengubah foreign key payment→sewa dari cascade menjadi restrict sebagai pertahanan terakhir terhadap delete dari jalur aplikasi lain atau query langsung.

Kriteria penerimaan: payment, invoice, pemasukan, bukti sewa, bukti payment, status kamar, dan lease tetap ada setelah request delete; pesan error tersedia; form delete tidak dirender pada detail; query delete langsung ditolak database; sewa tanpa payment tetap dapat dihapus dan cleanup buktinya berjalan.

## Validasi

| Kategori | Status | Bukti dan batasan |
|---|---|---|
| Functional | LULUS | Skenario payment lunas + invoice + ledger menguji UI/endpoint; query delete langsung ditolak FK; delete lease tanpa payment tetap lulus. |
| User flow & transaction | LULUS lokal | Lease–payment–invoice–Keuangan dibuat sintetis; request delete tidak mengubah satu pun record/file/status kamar. Row lock dan check diuji lokal; concurrency deployment belum diuji. |
| Responsive | BELUM DIUJI | Badge menggantikan form delete, tetapi browser ponsel/tablet/desktop tidak tersedia. |
| Bug & regression | LULUS | Suite final dan pemeriksaan build/cache/audit dicatat pada commit/PR; migrasi fresh → rollback terakhir → migrate lulus. |
| Retest | LULUS | Baseline tidak memberi error dan menjalankan cascade; skenario identik kini ditolak serta seluruh histori bertahan. |
| User testing | BELUM DIUJI | Simulasi agen menilai pesan menyebut alasan audit; UAT operator manusia belum dilakukan. |
| Security | LULUS dalam cakupan | Request langsung tidak dapat melewati guard UI; auth/permission route tetap berlaku; file privat tidak berubah. Bukan pentest penuh. |

Data seluruhnya sintetis. Status tetap **belum siap operasional penuh** sampai responsive browser, UAT manusia, dan concurrency/database/storage deployment representatif tervalidasi.

## Kelanjutan

Uji migrasi restrict pada engine/database deployment dan data legacy sebelum merge/deploy; SQLite lokal sudah lulus. Audit operasi delete lain yang dapat menghapus histori finansial, lalu staging rehearsal backup/restore.
