# Perbaikan aset UI/CSP — 15 Oktober 2026

## Bukti dan kriteria sebelum perubahan

- Main terbaru setelah fetch: `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`. PR #4/#5/#6 tetap draft; head #6 `27b5485c49133268b814e200c3c14ee1bf7a102a`. CI head tersebut run `38711637880` selesai gagal, mengonfirmasi checkpoint sebelumnya. Tidak ditemukan AGENTS.md pada pemeriksaan repo terdahulu; baca ulang bila muncul saat fetch berikutnya.
- Reproduksi browser yang tersedia: enam tes viewer/CSRF lulus, tiga tes admin gagal. Menu ponsel/tablet tidak terbuka; DataTables desktop tidak siap. Trace `38710698317` menunjukkan vendor Sneat `@main` memakai webpack eval, diblokir CSP. Error Helpers/Menu/jQuery mengikuti kegagalan tersebut.
- Kebutuhan operasional: navigasi, dropdown, dialog dan tabel kembali berfungsi tanpa menambah `unsafe-eval` atau melemahkan CSP. Batasi perubahan ke distribusi aset JavaScript dan regresi; tidak mengubah transaksi/izin/aturan bisnis.
- Rencana: periksa source resmi upstream, gunakan sumber berlisensi yang sama dan build produksi lokal/pinned untuk mempertahankan API yang sudah dipakai. Dependency tambahan hanya jika diperlukan untuk menggantikan library yang saat ini dimuat dari CDN.
- Kriteria penerimaan: tes admin ponsel/tablet/desktop yang gagal menjadi lulus pada CSP yang sama; tidak ada EvalError/ReferenceError baru; dropdown form dan penanganan input invalid berfungsi; backend/build/audit/regresi lama tetap lulus. Seluruh kategori diuji ulang, dan UAT manusia/concurrency deployment tetap dibedakan dari simulasi.
- Publikasi sebagai perubahan terfokus di atas PR #6, tanpa menduplikasi filter #5 atau merge ke main yang belum memenuhi gerbang mutu.

## Implementasi dan verifikasi awal

- Source resmi upstream dibaca pada commit `de094c2bf89e602054b7c14f46c9a3a5b9f6cac9`, versi Sneat 3.0.0, MIT. Empat source module dan notice lisensi disimpan dengan provenance; tidak menyalin bundle webpack development yang memakai eval.
- Vite membangun dua bundle klasik/sinkron (Helpers+config di head, core+menu+main di bawah body) agar inline Blade lama tetap memperoleh global jQuery/Bootstrap/Helpers/Menu pada urutan yang sama. jQuery 3.7.1, Bootstrap 5.3.3, Popper 2.11.8, Perfect Scrollbar 1.5.6, DataTables/Bootstrap integration 1.13.8 dipin sebagai dependency build, menggantikan library CDN yang sudah dipakai; bukan library fitur bisnis baru. Notice lisensi seluruh library ikut output.
- JS admin dimuat dari `public/ui`; CSS/font Sneat dipin ke commit yang sama, bukan mutable @main. `npm run build` mencakup `build:ui`; deployment harus menyertakan output `public/ui`. CSP dan seluruh aturan bisnis/izin tetap tidak diubah.
- Build bundle produksi lulus, tidak ditemukan direct eval/new Function pada output. Backend 190 tes / 1320 assertion dan frontend 7 tes lulus; cache route/view lulus; npm audit 0 vulnerability. Assertion browser diperkuat untuk menolak uncaught page errors/CSP errors, bukan dilonggarkan.
- Retest browser lokal belum berhasil. Unduhan Playwright masih invalid; runtime Chromium alternatif dipasang hanya di scratch (bukan dependency repo) tetapi ekstraksi berhenti pada operasi filesystem `chown /tmp/fonts` yang tidak didukung. Tidak mengubah permission atau melewati proteksi. Tes browser di CI yang sudah terbukti dapat berjalan menjadi gerbang validasi, belum diklaim lulus pada tahap ini.
- Self-review awal: upstream source asli berlisensi dan terpin, tidak mengubah CSS/app layout atau mengganti API template. `publicDir: false` mencegah penyalinan seluruh public ke output UI; hanya folder output terbangun yang dibersihkan. Tidak ada migrasi atau mutasi data operasional.

## Hasil final prioritas ini

- Source fix yang benar-benar diuji: `286e6df2832e956c019b10c23fff2aa6b93115e3`, tree `7052ad0b430e49dd74a4d4812caa888ae8757114`. [CI 38718569569](https://github.com/mvnz/kostSan/actions/runs/38718569569) sukses pada kedua job: backend/build/audit dan browser. Browser **9/9 lulus (25 detik)**, termasuk assertion tidak ada uncaught page error. Baseline tiga kegagalan admin menjadi lulus tanpa mengubah CSP atau melemahkan tes.
- [Artifact screenshot/report](https://github.com/mvnz/kostSan/actions/runs/38718569569/artifacts/11963372916), digest SHA-256 `901ce0ba5705db2911bc0a1cc485c5671d4d388fef6b1aee9a312face0952180`, kedaluwarsa 22 Oktober 2026. Tujuh halaman inti dan form reservasi dicapai pada 390x844, 768x1024, 1440x1000; tes memeriksa overflow dokumen. Screenshot form ponsel/tablet dan tabel pembayaran desktop diperiksa visual: kontrol/tabel dapat dibaca dan tidak keluar viewport. Ini bukan klaim seluruh dialog/alur aplikasi telah diuji visual.
- Retest HTTP pada SHA tersebut: admin/viewer membaca sepuluh halaman/detail; viewer edit 403; approval tanpa CSRF 419; dua retry approval valid tetap menghasilkan tepat satu invoice dan satu pemasukan Rp550.000, status lunas dan detail terkait terbaca. Hanya SQLite/data sintetis; WhatsApp dan pengiriman email dimatikan.
- Pengujian lokal source publikasi: PHPUnit 190 tes/1320 assertion, frontend 7 tes, clean npm ci/build, Composer validate/platform/audit, npm audit, migrasi rollback satu langkah lalu migrate, serta route/view cache semuanya lulus. Tidak ada advisory dependency terlapor. PR #5 berisi filter reservasi dan enam tes tambahan pada branch terpisah; tidak termasuk source ini, bukan tes yang dihapus.

| Kategori | Status | Bukti dan batas |
|---|---|---|
| Functional | LULUS | Suite backend dan browser: navigasi, tabel, dropdown/form valid serta checkout invalid lalu koreksi. Terbatas skenario yang dijalankan. |
| User flow & transaction | LULUS | Alur browser admin/viewer dan HTTP approval-retry/invoice/ledger; suite transaksi backend. Concurrency engine database deployment belum diuji. |
| Responsive | LULUS | Ketiga viewport mencapai tujuh halaman dan form; menu, DataTables, overflow dan screenshot diperiksa. Bukan seluruh kombinasi perangkat/dialog. |
| Bug & regression | LULUS | Reproduksi awal 3 kegagalan admin kini 9/9 browser lulus, backend 190 dan frontend 7 tetap lulus. |
| Retest | LULUS | Menu ponsel/tablet dan DataTables desktop diuji ulang pada CSP sama; assertion page error lebih ketat dan HTTP transaksi diulang pada SHA publikasi. |
| User testing | BELUM DIUJI | Simulasi agen admin/viewer/guest lulus; belum ada peserta manusia sehingga bukan UAT. |
| Security | LULUS | Lingkup lokal: role/403, CSRF/419, regresi private file/link di backend, audit dependency dan CSP tanpa unsafe-eval. Bukan pentest deployment menyeluruh. |

Self-review final eksplisit: perubahan aplikasi terbatas urutan/distribusi aset; global lama tetap tersedia, library berlisensi dan dipin, tidak ada perubahan authorization, money/date, transaksi, migrasi atau lifecycle file penghuni. Diff vendor besar berasal dari source upstream berlisensi, bukan abstraksi/fitur bisnis baru. Build output diabaikan git dan wajib dibangun saat deployment.

Status: blocker UI ini selesai dalam lingkup yang diuji, **belum siap operasional penuh**. PR #6 tetap draft bersama dependency PR #4; tidak merge. Berikutnya: validasi concurrency pada engine deployment terisolasi, cek konfigurasi/artefak deployment termasuk public/ui, dan UAT manusia. Skenario UAT: operator pada ponsel/tablet/desktop menelusuri kamar–penghuni–reservasi–sewa, memperbaiki input checkout salah, memproses pembayaran/approval dan mencocokkan invoice/ledger; viewer mencoba akses edit dan memastikan penolakan jelas. Catat peserta/perangkat, kemudahan, pesan error dan pemulihan tanpa data nyata atau menghubungi penghuni. Baca ulang main, PR/CI dan checkpoint ini sebelum prioritas berikutnya; jangan mengulang fix atau menumpuk fitur pada blocker yang belum tervalidasi.
