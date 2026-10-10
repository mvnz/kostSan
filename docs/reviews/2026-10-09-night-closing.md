# Penutupan rangkaian malam — 9 Oktober 2026

## Source dan status

- Main terakhir yang benar-benar di-fetch: `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`.
- Source fitur final yang dipublish dan diuji ulang dari GitHub: `ffe22b2590ff99bed071ffd9cb2c4ae8f3d4476e`, tree `d3a1b9301193fffb45fb78062b12f42cc5cda966`.
- Draft PR #4 tetap open, mergeable, belum di-merge. CI run 37851220944 masih berjalan saat checkpoint dibuat; periksa hasil terbaru sebelum merge.
- Status kesiapan: **belum siap operasional penuh**. Lingkup backend/HTTP/build yang diuji lulus, tetapi browser responsive, UAT pengguna nyata, dan concurrency pada database deployment belum terverifikasi.

## Kemajuan malam

Rangkaian menyelesaikan: lifecycle bukti Keuangan atomik; rekonsiliasi Pembayaran–Keuangan; detail read-only Keuangan/Pembayaran/Invoice; CSV Keuangan aman; pemulihan upload publik/storage; dependency kritis npm dan audit CI; periode Laporan Keuangan; hitungan/histori Hunian; snapshot cakupan tagihan multi-bulan dan pencegahan overlap bulk; pemulihan login role terbatas tanpa loop; link satu kali untuk mengirim bukti ke tagihan existing; pembalikan penuh immutable dengan offset ledger; timezone operasional default Asia/Jakarta.

Bug yang direproduksi sebelum perbaikan mencakup rollback bukti yang salah, tagihan tiga bulan menjadi tiga payment, redirect role tanpa dashboard, hitungan occupancy berdasarkan baris sewa, bulan laporan bergeser pada tanggal 31, dan ketiadaan alur aman untuk bukti existing/reversal. Setiap perubahan memiliki tes regresi di checkpoint tematik.

## Validasi penutupan pada source final

| Kategori | Status | Skenario dan bukti |
|---|---|---|
| Functional | LULUS dalam cakupan | 128 backend tests/901 assertions: valid, invalid dan boundary untuk fitur baru serta fungsi inti. 4 tes frontend dan Vite build lulus. |
| User flow & transaction | LULUS lokal | HTTP cookie/session/CSRF dengan data sintetis: kamar–penghuni–reservasi–sewa; link term dan existing bill; duplikasi link/bulk; approval retry; invoice; satu pemasukan; rekonsiliasi; CSV; reversal retry/offset; role restricted/no-view. Trigger rollback dan konsistensi nominal/periode lulus. Concurrency penulis pada DB deployment **BELUM DIUJI**. |
| Responsive | BELUM DIUJI | Chromium executable tidak tersedia dan install browser sebelumnya menghasilkan arsip invalid. Build bukan bukti responsif. Perlu ponsel/tablet/desktop untuk navigasi, form, tabel, dialog, overflow, chart, link publik dan reversal. |
| Bug & regression | LULUS | Full suite 128/901 pada exact SHA remote; frontend 4/4; build; route/view cache; migration fresh + rollback/remigrate; Composer platform/validate/audit; npm audit; diff/format. |
| Retest | LULUS | Reproduksi awal dan bug implementasi (tanggal boundary, invoice rollback, access loop, duplicate coverage) diulang; full suite dan HTTP smoke final lulus. |
| User testing | BELUM DIUJI | Simulasi agen admin/operator/penghuni lulus via HTTP, termasuk pesan pemulihan. UAT manusia dan penilaian visual belum tersedia; skenario ada pada tiap checkpoint. |
| Security | LULUS dalam cakupan | Auth/role/object relation, CSRF, rate limit, SQL-injection style input, XSS escaping, CSV formula, token expiry/retry/revoke, upload privat/rollback, secret scan, npm/Composer audit. Bukan pentest penuh. |

Fresh migration, rollback/remigrate terakhir mencakup `payment_id` link existing dan `payment_reversals`. HTTP smoke final dijalankan lagi setelah fetch SHA remote. Tidak ada migrasi produksi, data penghuni nyata, atau pesan keluar.

## Backlog kelanjutan

1. Jalankan responsive browser pada 3 viewport dan UAT nyata; PR tetap draft sampai bukti tersedia.
2. Uji transaksi serentak pada engine/database deployment representatif, khususnya link/token, bulk billing, approval, reversal dan reservasi/sewa.
3. Rancang deduplikasi manual yang membedakan duplikat dari cicilan sah; jangan memblokir cicilan tanpa definisi bisnis.
4. Sediakan pencocokan manual legacy payment–income yang eksplisit dan auditable, tanpa tebakan/backfill otomatis.
5. Definisikan partial refund/remaining balance bila dibutuhkan; implementasi kini hanya full reversal dan monthly billing setelah extension.
6. Verifikasi strategi deployment timezone/cache (`APP_TIMEZONE=Asia/Jakarta`, `config:clear/cache`) dan backup/migration rehearsal terpisah sebelum produksi.

Sebelum pekerjaan berikutnya: fetch main dan PR terbaru, pastikan CI final sukses, baca checkpoint ini serta `2026-10-09-payment-reversal.md`, dan hindari perubahan paralel pada PR #4.
