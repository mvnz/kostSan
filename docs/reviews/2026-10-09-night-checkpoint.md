# Checkpoint pengembangan malam — 9 Oktober 2026

## Source dan koordinasi

Main yang di-fetch: `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`. Pekerjaan dilanjutkan dari draft PR #4 head `1ed30f1faa9cd9fe03d3cb0f264ab0ef975b64d2`, bukan checkout lama. CI head itu sukses, tidak ada issue non-PR terbuka, AGENTS.md, atau proses lain yang terdeteksi. Checkpoint rekonsiliasi/Keuangan `2d49f521221fe5c46a2b74cf38a189f6a90e9554` sudah masuk ke PR dengan tree yang cocok dan CI 37817443206 sukses. Lanjutan di sini dibangun pada commit remote itu. Jangan membuat PR duplikat. SHA/tree akhir dan CI lanjutan dicatat pada PR setelah upload dan fetch verifikasi.

## Prioritas yang dikerjakan berurutan

1. Siklus bukti Keuangan: tiga kegagalan direproduksi, dibenahi dengan transaksi/row lock, pembersihan unggahan pada rollback dan bukti lama setelah commit. Rincian di `2026-10-09-finance-reconciliation.md`.
2. Rekonsiliasi pembayaran–Keuangan read-only: lunas tanpa ledger dan mismatch nominal/tanggal/jenis/kategori/status; filter bulan, pagination, dua izin view, private/no-store, tanpa backfill histori manual.
3. Detail Keuangan read-only; sebelumnya show mengarahkan view-only ke Edit.
4. Export CSV Keuangan: tombol tanpa aksi diganti endpoint streaming. Bulan/jenis mengikuti filter tabel, termasuk semua halaman; pencarian global tabel tidak memengaruhi CSV. Jumlah dua desimal, sumber manual/otomatis dan ID pembayaran, UTF-8 BOM, proteksi formula; tidak ada path bukti. Tes lebih dari 200 baris membuktikan chunk tanpa duplikasi.
5. Unggahan publik: dua kegagalan DB direproduksi. Pembayaran gagal meninggalkan bukti; pendaftaran gagal meninggalkan foto identitas. Kini rollback membersihkan semua berkas baru, mempertahankan token, dan retry berhasil.
6. Storage failure: tiga reproduksi menunjukkan disk mengembalikan false tetapi aplikasi menyatakan sukses. PrivateUpload menolak kegagalan dengan validasi field 422. Create tidak menghasilkan pembayaran/invoice/ledger, replacement mempertahankan bukti lama, kegagalan foto kedua membersihkan foto pertama dan menjaga token.
7. Dependency kritis: audit npm menemukan dua advisori melalui concurrently → shell-quote (GHSA-pqg4-j6r4-53mv). Update tanpa force/scripts: concurrently 9.2.4 → 9.2.5, shell-quote 1.9.0 → 1.12.0. Audit setelah perbaikan nihil; npm ci dari lock, tes/build lulus. CI sekarang menjalankan npm audit --audit-level=low. Tidak mengubah package.json atau major version.
8. Detail Pembayaran dan Invoice: reproduksi show 302 menuju Edit untuk view-only. Kini 200 read-only, escaped output, private/no-store; tombol edit/approve dan sumber lintas modul sesuai izin. Transaksi lunas dan invoice otomatis tidak menawarkan edit.
9. Laporan Keuangan HTML/PDF: reproduksi Februari bergeser ke Maret pada tanggal sistem 31, bulan invalid diterima. Format bulan di-reset ke hari pertama dan divalidasi; retest HTML, render PDF, batas bulan dan input invalid lulus.
10. Laporan Hunian: reproduksi kamar terhitung lebih dari sekali dari sewa berurutan dalam satu bulan dan tahun invalid diterima. Kini distinct kamar, interval checkout eksklusif, validasi integer 1900–9999. UI menjelaskan cakupan: kamar dengan sewa aktif pada sebagian bulan; bukan rata-rata harian, belum histori sewa selesai.

## Pengujian source akhir

| Kategori | Status | Bukti / batas |
|---|---|---|
| Functional | LULUS dalam cakupan | Rekonsiliasi, detail per role, CSV bulan/jenis/pecahan/formula/chunk, unggahan valid/gagal/parsial, batas bulan/tahun dan laporan/PDF. |
| User flow & transaction | LULUS lokal | Suite registration → pembayaran → approval → invoice/ledger; HTTP session/CSRF, navigasi modul inti, detail Invoice/Pembayaran/Keuangan, filter/pagination, approval → satu ledger → retry → rekonsiliasi kosong → CSV. Rollback DB dan file gagal diuji. Concurrency database deployment BELUM DIUJI. |
| Responsive | BELUM DIUJI | Playwright executable tidak ada; unduhan chromium headless menghasilkan arsip tidak valid. Perlu browser 375×812, 768×1024, 1440×900, termasuk klik filter/CSV, navigasi sumber, dialog, overflow/form. |
| Bug & regression | LULUS | 103 backend tests / 673 assertions, 4 frontend tests, Vite build, fresh SQLite migrate + rollback/remigrate, route/view compile, diff check; npm ci --ignore-scripts dari lock baru. |
| Retest | LULUS | Semua skenario reproduksi finansial/upload/read-only/laporan lulus sesudah perbaikan, diikuti full suite. |
| User testing | BELUM DIUJI | Simulasi agen HTTP dan role tests lulus. Visual usability, klik JS filter-ke-CSV dalam browser, dan UAT pengguna nyata belum dilakukan. |
| Security | LULUS dalam cakupan | Login/CSRF HTTP 419, role denial/detail/mutasi, dual-role report, output XSS escaped, CSV formula guard, private documents/public token/upload suite. Composer locked dan npm audit nihil. Scan pola secret tracked: 0; .env tidak tracked. Bukan pentest penuh. |

Semua data sintetis; SQLite baru terisolasi. Tidak ada server/database produksi, force push, pesan atau invoice ke penghuni. Composer phar validate/platform/audit lulus; Composer distro mengalami ketidakcocokan Normalizer, jangan menambah dependency aplikasi untuk memperbaiki runtime tool. Browser tetap menghalangi merge: PR tetap draft.

## Kelanjutan, belum penutupan pukul 05.00

Waktu pemeriksaan terakhir sekitar 00.49 WIB, masih dalam jendela malam. Ini checkpoint satu eksekusi, bukan klaim program malam selesai. Pada eksekusi berikut, fetch main dan PR #4 terbaru, cek perubahan/CI/pekerjaan aktif, dan teruskan backlog berikut. Jangan ulangi fitur di atas. Tetap jalankan tujuh kategori testing tiap akhir eksekusi.

Prioritas berikut: deduplikasi cakupan pembayaran multi-bulan vs manual/bulk/link. Source sekarang menghitung total seluruh term pada link tetapi hanya menyimpan satu periode; hasMonthlyBill hanya melihat bulan periode. Perlukan metadata cakupan eksplisit, bukan tebakan nominal historis; term perlu snapshot saat penagihan. Hindari perubahan yang menolak cicilan yang sah tanpa definisi produk. Berikutnya reversal/refund yang menjaga histori, pencocokan pemasukan manual lama dengan bukti, histori laporan hunian, concurrency pada DB deployment. Kegagalan penghapusan berkas setelah commit masih membutuhkan monitoring/cleanup administratif.

UAT yang disiapkan: admin filter/export Keuangan dan cocokkan nominal; view-only telusuri Invoice → Pembayaran → Keuangan sesuai role; sengaja unggah berkas gagal dan ulangi setelah error; rekonsiliasi data sintetis lama tanpa menggandakan pemasukan; pilih Februari pada tanggal 31 dan cek HTML/PDF; pergantian penghuni dalam bulan tidak menggandakan kamar. Ulangi pada tiga viewport. Jangan klaim UAT manusia selesai tanpa partisipasi dan bukti.
