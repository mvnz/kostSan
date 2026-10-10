# Checkpoint cleanup dokumen penghuni — 10 Oktober 2026

## Source dan temuan

Prioritas dimulai dari draft PR #4 head `f8f6ac414ef79e8cef1588de68635e9ab6a9c392`, exact tree `a17ce0198e9114c2c8feaa0fe2d13b45f4d5fedb`.

Pendaftaran publik menyimpan foto KTP dan selfie di disk privat dan aman saat rollback, tetapi penghapusan record penghuni yang sudah bebas relasi tidak menghapus kedua dokumen. Reproduksi membuktikan record hilang sementara dua file identitas tetap menjadi orphan tanpa queue.

## Perubahan dan manfaat

Delete penghuni sekarang berjalan dalam transaksi dan mengunci row target. Setelah commit, setiap path KTP/selfie diteruskan ke `PrivateFileCleanup`. Storage gagal membuat satu job per dokumen; kegagalan delete database tidak memicu cleanup, sehingga record dan dokumen tetap konsisten untuk retry.

Kriteria penerimaan: delete sukses menghapus dua dokumen tanpa queue; storage false tetap menghapus record dan membuat dua queue; trigger delete gagal mempertahankan record, dua dokumen, dan queue kosong; pemeriksaan relasi yang sudah ada tetap mencegah delete.

## Validasi

| Kategori | Status | Bukti dan batasan |
|---|---|---|
| Functional | LULUS | Tiga regresi menguji sukses, dua kegagalan storage, dan rollback database. Guard relasi tetap dicakup suite existing. |
| User flow & transaction | LULUS lokal | Pendaftaran menghasilkan dokumen privat; delete bebas relasi → commit → cleanup/queue diuji pada SQLite dan fake storage. Concurrency deployment belum diuji. |
| Responsive | BELUM DIUJI | Tidak ada UI baru; browser ponsel/tablet/desktop tetap belum tersedia. |
| Bug & regression | LULUS | Suite final, build, cache, dan audit dicatat pada commit/PR. |
| Retest | LULUS | Baseline meninggalkan dua file dan tidak membuat queue; skenario identik lulus setelah perbaikan. |
| User testing | BELUM DIUJI | Simulasi agen operator delete dan error recovery lulus; UAT manusia belum dilakukan. |
| Security | LULUS dalam cakupan | Dokumen identitas tetap privat; path/error cleanup tidak ditampilkan di Dashboard; delete memerlukan auth/izin modul. Bukan pentest penuh. |

Seluruh data dan dokumen sintetis. Status tetap **belum siap operasional penuh** hingga responsive browser, UAT manusia, dan concurrency/database/storage deployment representatif tervalidasi.

## Kelanjutan

Audit lifecycle data sensitif lainnya, lakukan staging rehearsal storage/backup/restore, dan uji concurrency engine deployment. Partial payment/refund tetap menunggu definisi bisnis.
