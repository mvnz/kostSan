# Filter status reservasi — 13 Oktober 2026

## Kebutuhan dan batas cakupan sebelum implementasi

- Main terbaru setelah fetch: `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`; default branch dikonfirmasi lewat metadata GitHub. Tidak ditemukan AGENTS.md.
- PR aktif #4: head `bc19e0195a899d33359afefa3b54624b79f8179d`, CI run `38089814717` sukses. Lanjutkan source ini karena main belum memuat pekerjaan sebelumnya; jangan menduplikasi PR.
- Backlog checkpoint 11 Oktober meminta filter status reservasi. Controller index mengambil seluruh histori tanpa filter; operator harus mencari status di tabel campuran. Status kedaluwarsa/dikonversi sudah ada.
- Manfaat: operator dapat memilih antrean menunggu/dikonfirmasi atau menelusuri histori berdasarkan status. Cakupan hanya GET daftar reservasi; tidak mengubah record, lifecycle, nominal, notifikasi, atau izin.
- Kriteria penerimaan: kelima status dapat dipilih; kosong/default menampilkan semua; hasil tanpa kecocokan tetap valid; input tidak dikenal/array ditolak; pilihan dipertahankan dan Reset tersedia; viewer tetap tanpa hak mutasi dan role tanpa view ditolak.
- Reproduksi pada source PR sebelum implementasi: `PAO_DISABLE=1 php vendor/bin/phpunit --filter ReservationFilterTest --fail-on-warning --fail-on-risky` menghasilkan 5 tes, 4 gagal: hasil tidak terfilter, kontrol filter belum ada, input invalid diterima, dan status tanpa kecocokan tetap menampilkan histori lain. Tes izin yang sudah ada lulus.

## Validasi akhir

- Implementasi: filter GET server untuk kelima status, kontrol berlabel dan layout mengikuti Bootstrap repo, pilihan tetap terpilih, Reset kembali ke daftar lengkap. Tidak ada dependency/migrasi baru.
- Smoke HTTP menemukan loop redirect saat URL sebelumnya juga berisi status invalid. Regresi `test_invalid_html_filter_recovers_even_when_previous_url_is_invalid` gagal sebelum perbaikan (redirect ke `?status=invalid`); sekarang validasi HTML mengarah ke route daftar bersih, JSON tetap 422. Input GET diambil dari query saja.
- Tes terarah final: 6 tes / 54 assertion. Full suite final: **196 tes / 1374 assertion lulus**; SHA publikasi dicantumkan di PR. Baseline suite sebelum perbaikan redirect: 195 / 1368 lulus.
- HTTP sungguhan dengan SQLite sintetis, session file, CSRF aktif dan server lokal: admin/viewer login, kelima filter, pilihan terpilih, reset, error JSON/HTML dan pemulihan, logout tanpa CSRF 419, logout valid sukses. Role tanpa view: 403. Tidak ada notifikasi keluar pada alur GET ini.
- Composer validate strict, platform requirements dan audit locked lulus (tanpa advisory); npm test 4 tes, build Vite dan audit 0 vulnerability lulus. Peringatan fontaine opsional tetap ada.
- SQLite baru: migrate:fresh → rollback --step=1 → migrate ulang lulus. Route/view cache lulus. Scheduler terdaftar pada database uji. Percobaan awal schedule:list tanpa env DB uji gagal karena path default database tidak ada; pengulangan dengan env DB uji lulus, bukan bug aplikasi.
- Browser Playwright launch gagal karena executable tidak tersedia. Install Chromium headless shell mencoba beberapa kali; arsip unduhan invalid (`End of central directory record signature not found`), sehingga tidak ada responsive test yang berhasil. Jangan menganggap build/HTML sebagai pengganti.

| Kategori | Status | Bukti / batasan |
|---|---|---|
| Functional | LULUS | Filter semua/tiap status/kosong, invalid/array, tanpa hasil, izin dan pemulihan error; tes HTTP dan regresi terarah. |
| User flow & transaction | LULUS lokal | Suite penuh mencakup pendaftaran→sewa→pembayaran→approval→invoice/ledger, konversi reservasi, retry dan rollback sintetis; smoke HTTP filter admin/viewer lulus. Concurrency database deployment BELUM DIUJI. |
| Responsive | BELUM DIUJI | Browser tidak tersedia dan instalasi gagal; diperlukan ponsel 390px, tablet 768px, desktop 1440px dengan aset CDN termuat. |
| Bug & regression | LULUS | Filter dan redirect ditest, suite backend/frontend, build, migrasi/cache; 196 tes / 1374 assertion final lulus. |
| Retest | LULUS | Reproduksi redirect invalid diulang dengan tes regresi dan HTTP, kini kembali ke daftar bersih dengan pesan error. |
| User testing | BELUM DIUJI | Simulasi agen melalui HTTP admin/viewer/tanpa izin lulus; UAT manusia dan evaluasi visual belum tersedia. |
| Security | LULUS dalam lingkup lokal | Invalid/injection-like/array ditolak; hak GET dan mutasi viewer, auth/session/CSRF melalui HTTP; suite dokumen privat/link/rollback/XSS dan dependency audit lulus. Bukan pentest deployment. |

## Self-review diff dan kelanjutan

- Review mandiri eksplisit: diff terbatas controller, view, tes, README dan checkpoint; query terparameterisasi, eager loading dipertahankan, output label statis/escaped, tanpa mutasi atau perubahan transaksi/file lifecycle. Tidak mengklaim reviewer kedua/manusia.
- Branch kerja `codex/operational-validation-20261013` dari head PR #4. Buat PR kecil dengan base `codex/payment-atomicity-reconciliation-20261008`; hanya delta filter yang direview, bukan salinan keseluruhan pekerjaan ke PR main baru. PR #4 tetap draft; jangan merge ke main sebelum blocker terselesaikan. Setelah validasi browser, perubahan PR kecil dapat digabung ke branch kerja tanpa force push.
- Skenario UAT manusia yang disiapkan: operator pilih Menunggu, buka Detail, kembali dan pilih Dikonfirmasi; pilih status tanpa hasil, Reset; viewer dapat membaca namun tidak mengedit; URL status invalid menampilkan error lalu dapat memilih status valid. Pada ketiga viewport periksa navigasi, label/tombol, dropdown, tabel/dialog, overflow dan penyelesaian tugas. Catat peserta, perangkat dan hasil; belum dijalankan manusia.
- Prioritas berikut: akses browser/staging sintetis, concurrency pada engine deployment, UAT manusia. Audit konsistensi birthday untuk status menunggak belum dilakukan; partial payment/refund memerlukan definisi bisnis. Secret-pattern scan tracked files: tidak ada private key/GitHub token/AWS access key terdeteksi (scan pola terbatas). Tidak ada tindakan produksi atau komunikasi penghuni.
