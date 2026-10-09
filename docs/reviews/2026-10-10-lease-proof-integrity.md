# Checkpoint integritas bukti sewa — 10 Oktober 2026

## Source dan temuan

Prioritas dimulai dari draft PR #4 head `6d77a44bd0793da5c30cb6a1259690ccb1d1e340`, exact tree `ea7e3656a9d706616012f40dbeee5594afea7c38`.

Controller Data Sewa masih menyimpan bukti langsung melalui adapter. Reproduksi membuktikan empat celah: storage yang mengembalikan `false` tetap menghasilkan redirect sukses; insert database gagal meninggalkan upload baru; update database gagal meninggalkan replacement baru; delete sewa berhasil tetapi bukti lama tetap orphan.

## Perubahan dan manfaat

Create/update bukti sewa sekarang memakai `PrivateUpload`, sehingga kegagalan storage menjadi error validasi pada field bukti dan tidak mengubah database/status kamar. Upload sukses yang kemudian mengalami rollback database dibersihkan melalui `PrivateFileCleanup`; bila cleanup juga gagal, path tetap masuk queue.

Delete sewa kini berjalan dalam transaksi dengan row lock. Bukti lama dihapus setelah commit; kegagalan delete fisik tidak membatalkan transaksi database tetapi dicatat sebagai job `lease delete`. Update mempertahankan bukti lama sampai commit dan membersihkan replacement bila rollback.

Kriteria penerimaan: storage false menolak create/update; create rollback tidak meninggalkan file/sewa; update rollback menyisakan hanya bukti asli; delete sukses menghapus bukti dan mengembalikan status kamar; delete storage false tetap menghapus record tetapi membuat satu queue.

## Validasi

| Kategori | Status | Bukti dan batasan |
|---|---|---|
| Functional | LULUS | Create/update storage false, insert/update trigger rollback, delete sukses, dan delete cleanup false diuji dengan data sintetis. |
| User flow & transaction | LULUS lokal | Room–resident–lease–proof create/update/delete, status kamar, rollback, after-commit cleanup, dan queue diverifikasi pada SQLite/filesystem fake. Concurrency deployment belum diuji. |
| Responsive | BELUM DIUJI | Tidak ada UI baru; error memakai mekanisme validasi form existing. Browser ponsel/tablet/desktop tetap belum tersedia. |
| Bug & regression | LULUS | Suite final, frontend, build, cache, dan audit dicatat pada commit/PR. |
| Retest | LULUS | Empat reproduksi baseline gagal sebelum perbaikan; seluruh skenario lulus setelah perubahan. |
| User testing | BELUM DIUJI | Simulasi agen operator mencakup retry setelah storage gagal; UAT manusia belum dilakukan. |
| Security | LULUS dalam cakupan | Bukti tetap pada disk privat; cleanup failure dicatat tanpa mengekspos path ke Dashboard. Auth/permission dan suite upload privat dijalankan. Bukan pentest penuh. |

Data seluruhnya sintetis; tidak ada storage/database produksi. Status aplikasi tetap **belum siap operasional penuh** hingga responsive browser, UAT manusia, dan concurrency/database/storage deployment representatif tervalidasi.

## Kelanjutan

Audit file lifecycle controller lain yang tersisa, uji database deployment nyata, serta siapkan UAT operator. Partial payment/refund tetap memerlukan definisi bisnis sebelum implementasi.
