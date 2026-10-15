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
