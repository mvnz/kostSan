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
