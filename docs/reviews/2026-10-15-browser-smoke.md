# Browser smoke terisolasi — 15 Oktober 2026

## Kebutuhan, cakupan dan kriteria sebelum implementasi

- Fetch terbaru: main `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`, default branch `main`; tidak ditemukan AGENTS.md atau issue terbuka. PR #4 `bc19e0195a899d33359afefa3b54624b79f8179d` dan PR #5 `1a7ed3fdc95c03f8eef97617217248339215bf74` masih draft, tanpa perubahan baru. CI #4 run `38089814717` sukses.
- Blocker terverifikasi pada checkpoint sebelumnya: tidak ada tes browser di repo/CI; executable Chromium lokal tidak tersedia dan unduhan arsip gagal. Build dan HTTP tidak membuktikan responsivitas. Jangan menambah fitur bisnis untuk menggantikan validasi yang hilang.
- Peningkatan: harness browser yang dapat dijalankan di CI dengan fixture sintetis, viewport ponsel 390×844, tablet 768×1024 dan desktop 1440×1000, serta trace/screenshot kegagalan. Dependency Playwright hanya untuk development/testing dan diperlukan untuk browser yang nyata.
- Kriteria: harness hanya dapat menyasar localhost miliknya dan database SQLite khusus pengujian; fixture menolak env/database tidak aman sebelum penulisan; login admin/viewer dan denial izin dapat diperiksa; menu mobile dapat dibuka; halaman operasional dan form/detail tampil, tidak ada overflow dokumen; error input dapat dipulihkan; CSS/JavaScript CDN benar-benar termuat. Gagal load aset tidak boleh dilaporkan lulus responsif.
- Cakupan tidak mencakup UAT manusia, semua dialog/halaman, concurrency engine deployment atau produksi. Tidak mengubah aturan bisnis, mengirim notifikasi penghuni atau memakai data nyata.
- Rencana publikasi: PR kecil ke branch PR #4, independen dari filter #5, tanpa duplikasi diff seluruh main. Tetap draft jika validasi browser belum lulus.

## Hasil

- Playwright development dependency dipin `1.64.1` melalui npm, tanpa upgrade dependency aplikasi. Sembilan skenario browser (3×3 viewport) terdaftar oleh `npm run test:browser -- --list`.
- Lokal: PHP lint dan Node syntax lulus; backend 190 tes / 1320 assertion lulus (source base #4, tidak mengulang filter PR #5); frontend 7 tes lulus, termasuk penolakan fixture produksi/database arbitrer dan konfigurasi browser non-isolasi. Build lulus; npm audit 0 vulnerability dan Composer validate/platform/audit lulus.
- Fixture sukses pada database SQLite baru; pemanggilan kedua ditolak sebelum menambah data. Migrasi terbaru rollback dan migrate ulang lulus. Route/view cache serta schedule:list lulus pada database uji.
- Browser lokal belum dapat dijalankan: instalasi Chromium headless shell `v1248` menghasilkan arsip invalid; percobaan satu skenario berhenti pada browser launch, bukan kegagalan assertion aplikasi. Responsivitas BELUM DIUJI sampai CI browser benar-benar berjalan.
- Self-review eksplisit: hanya harness, fixture, dev dependency, CI dan dokumentasi. Tidak mengubah controller/model/aturan bisnis. Fixture guard diperketat untuk DB_URL dan cached configuration; tidak ada reset/truncate otomatis. Menu mobile menggunakan jalur Data Sewa yang benar-benar ada; dropdown form harus diklik melalui kontrol UI, tidak memaksa select tersembunyi.
- Jangan merge sebelum hasil CI browser diperiksa. Jika CI menemukan overflow/komponen gagal, simpan bukti lalu reproduksi dan perbaiki secara terfokus; jangan melemahkan assertion agar hijau. UAT manusia dan concurrency engine deployment tetap belum diuji.

## Hasil browser nyata dan blocker prioritas

- Head aplikasi/harness diuji di CI: `766f18314de766e9a91aef687d42b53aaf97b620`, tree `921c2653f00c71f79d4d8e9ab7d2b6d653b9feca`, draft PR #6. Job backend/build/audit sukses. Browser job pada [run 38710698317](https://github.com/mvnz/kostSan/actions/runs/38710698317) **GAGAL: 6 lulus / 3 gagal**. Ini temuan baru, bukan responsive yang belum pernah dijalankan.
- Ketiga tes admin gagal: ponsel/tablet tidak membuka menu setelah klik hamburger; desktop menunggu DataTables yang tidak siap. Enam tes viewer/CSRF lulus pada ketiga viewport. Form reservasi dan tujuh halaman belum seluruhnya tercapai karena tes berhenti pada blocker awal; jangan klaim cakupan penuh.
- Bukti disimpan pada [artifact 11960224387](https://github.com/mvnz/kostSan/actions/runs/38710698317/artifacts/11960224387) (screenshot dan trace; kedaluwarsa 22 Oktober 2026). Screenshot ponsel diperiksa: halaman memiliki styling, namun menu tetap tertutup setelah klik.
- Trace mengonfirmasi `Uncaught EvalError` pada `helpers.js`, jQuery, Popper, Bootstrap, Perfect Scrollbar dan `menu.js` dari `https://cdn.jsdelivr.net/gh/themeselection/sneat-bootstrap-html-admin-template-free@main/assets/`. CSP `script-src` tidak mengizinkan `unsafe-eval`. Error downstream: Helpers/getCssVar tidak tersedia, `Menu is not defined`, `jQuery is not defined`, `$ is not defined`. Jadi ini inkompatibilitas aset JavaScript dengan CSP, bukan sekadar dugaan layout atau timeout internet.
- Belum diperbaiki: perlu memilih/membangun aset produksi tanpa eval yang kompatibel dan terpin, lalu mengulang tes browser seluruh viewport serta alur lama. Jangan menambah `unsafe-eval`, menonaktifkan CSP, memaksa DOM menu terbuka, mengecualikan tes, atau menganggap kelulusan backend sebagai pengganti. Perubahan lintas aset tersebut belum divalidasi sehingga tidak dimasukkan sebagai fix setengah jadi pada PR harness.
- HTTP sungguhan setelah fetch ulang source publikasi: admin/viewer membaca 10 halaman/detail, viewer edit JSON 403, approval tanpa CSRF 419. Approval valid diulang dua kali: status lunas, tepat satu invoice dan satu ledger pemasukan Rp550.000; detail invoice/ledger dapat dibaca. WhatsApp dimatikan dan hanya data sintetis digunakan. Secret-pattern scan tracked files: tidak ada pola private key/GitHub token/AWS access key terdeteksi (bukan secret audit menyeluruh).

| Kategori | Status | Bukti / tindak lanjut |
|---|---|---|
| Functional | GAGAL | Backend 190/1320 dan guard frontend 7 lulus; interaksi admin browser terhambat JavaScript/CSP. |
| User flow & transaction | GAGAL | Transaksi HTTP/retry dan suite lokal lulus; admin browser tidak dapat menyelesaikan navigasi. Concurrency DB deployment belum diuji. |
| Responsive | GAGAL | Klik menu ponsel/tablet gagal; desktop DataTables tidak siap. Belum semua halaman/form tercapai. |
| Bug & regression | GAGAL | Harness mereproduksi blocker UI yang tidak terdeteksi backend/build; 6/9 browser lulus. |
| Retest | BELUM DIUJI | Belum ada fix CSP/aset untuk diretest; pemeriksaan guard/fixture dan source publikasi lokal telah diulang lulus. |
| User testing | BELUM DIUJI | UAT manusia belum tersedia; simulasi agen browser parsial gagal, viewer/CSRF lulus. |
| Security | LULUS dalam lingkup lokal | Auth/izin/CSRF/fixture guard, dependency audit dan suite private file/link lulus; CSP dipertahankan. Bukan pentest deployment. |

Prioritas berikutnya adalah blocker aset produksi/CSP di atas, sebelum fitur bisnis baru. PR #4/#5/#6 tetap draft dan tidak di-merge. Pertahankan fixture dan assertion ini sebagai reproduksi; setelah fix, wajib ulang semua kategori. Untuk UAT manusia berikutnya, operator harus mampu membuka menu, menelusuri kamar/penghuni/reservasi/sewa/tagihan dan menyelesaikan form pada ketiga viewport; catat peserta/perangkat, pesan error dan pemulihan. Belum ada pengguna lain yang dihubungi.

Pembaruan lanjutan 15 Oktober 2026: blocker aset/CSP di atas sudah diperbaiki pada `286e6df2832e956c019b10c23fff2aa6b93115e3`; CI browser 9/9 lulus. Bukti awal di dokumen ini tetap merupakan catatan historis, bukan status akhir. Lihat [checkpoint perbaikan dan batas validasi](2026-10-15-csp-production-ui.md).
