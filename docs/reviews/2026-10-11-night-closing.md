# Penutupan rangkaian malam — 11 Oktober 2026

## Source dan publikasi

- Branch utama yang diperiksa tetap berawal dari `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`.
- Branch kerja lokal: `codex/final-3758f92`.
- Draft PR: `https://github.com/mvnz/kostSan/pull/4` pada branch `codex/payment-atomicity-reconciliation-20261008`.
- Head PR terakhir yang berhasil dipublikasikan malam ini: `61f02b8120e12276dac863b8261aa967be4097c4`.
- Commit lokal setelah head PR: `b64c8e825a1ead79caf88c94da9e4e1a95dc32a6` dan `8a1306172d9d2c50826560dcc4e6aa8c69e8c419`. Publikasi terhambat batas penggunaan sementara konektor GitHub; tidak dilakukan force push atau bypass.
- PR tetap draft dan tidak di-merge karena responsive browser, UAT manusia, dan concurrency engine deployment belum terverifikasi.

## Hasil malam

1. Sewa `menunggak` diperlakukan sebagai penghuni berjalan pada status kamar, laporan, dashboard, billing massal, perpanjangan, dan reminder jatuh tempo tanpa menghapus status utang.
2. Peta kamar tidak lagi menempatkan data kamar/penghuni ke inline JavaScript/`innerHTML`; kontrol UI dan endpoint selesai/perpanjang mengikuti hak Data Sewa yang tepat. Konfirmasi delete master data juga tidak lagi memasukkan teks database ke inline script.
3. Reservasi terkonfirmasi dapat dikonversi menjadi sewa secara atomik. Kamar/penghuni/periode diprefill, relasi dua arah tersimpan, manipulasi/retry ditolak, kegagalan menutup reservasi me-rollback sewa, dan histori hasil konversi immutable.
4. Reservasi selesai otomatis menjadi `kedaluwarsa` pada 00.05; batas checkout eksklusif konsisten dengan availability. Command idempotent, locked, dan tidak mengubah waiting/open-ended/converted.
5. Dashboard menghitung Reservasi Aktif saja dan tidak lagi menghitung kamar `perbaikan` sebagai kamar terisi.
6. Bootstrap tes menghapus route cache stale sebelum boot sehingga regresi benar-benar menjalankan route source saat ini; route cache tetap dikompilasi terpisah setelah suite.

Checkpoint rinci: `2026-10-10-overdue-occupancy-billing.md`, `2026-10-10-room-map-xss-permissions.md`, `2026-10-10-overdue-extension-reminders.md`, `2026-10-10-reservation-conversion.md`, `2026-10-10-reservation-expiry.md`, dan `2026-10-10-dashboard-reservation-metric.md`.

## Validasi penutupan

- PHPUnit: **190 tes / 1320 assertion**, lulus tanpa warning/risky.
- Frontend Node: **4 tes**, lulus.
- Vite production build: lulus; hanya peringatan package opsional `fontaine`.
- Route cache dan Blade view cache: lulus.
- Composer validate strict dan audit: lulus, tidak ada advisory.
- npm audit: 0 vulnerability.
- SQLite sintetis terisolasi: migrate fresh, rollback migrasi terakhir, migrate ulang: lulus.
- Scheduler terisolasi: expiry reminder 08.00, birthday 08.10, reservation expiry 00.05, cleanup file privat hourly terdaftar.
- Tidak ada pesan/invoice nyata, data penghuni nyata, database produksi, atau migrasi produksi yang digunakan.

| Kategori | Status | Bukti dan batasan |
|---|---|---|
| Functional | LULUS dalam lingkup | Konversi, expiry, status menunggak, metrik dashboard, input valid/tampered/batas tanggal, dan UI permission diuji otomatis. |
| User flow & transaction | LULUS lokal | Data sintetis mencakup kamar–penghuni–reservasi–sewa–tagihan–approval–invoice–keuangan; trigger membuktikan rollback konversi. Persaingan nyata DB deployment belum diuji. |
| Responsive | BELUM DIUJI | Browser lokal tidak tersedia; build tidak dijadikan bukti responsive. |
| Bug & regression | LULUS | Suite backend penuh, frontend, build, cache, dan migrasi lulus. |
| Retest | LULUS | Bypass reservasi penghuni sama, rollback migrasi, checkout eksklusif, XSS inline, dan agregasi kamar diulang pada kode final. |
| User testing | BELUM DIUJI | Simulasi agen/admin/operator/penghuni melalui HTTP lulus; UAT manusia nyata belum dilakukan. |
| Security | LULUS dalam lingkup | Auth/role, CSRF, ID tampering, output XSS, file privat, dependency audit, dan histori immutable tercakup; bukan pentest deployment. |

## Backlog berikutnya

1. Publikasikan dua commit lokal tersisa dan checkpoint penutupan ketika konektor GitHub pulih, lalu pastikan CI pada SHA yang sama lulus.
2. Uji responsive ponsel/tablet/desktop dan lakukan UAT manusia dengan skenario operasional terdokumentasi.
3. Uji transaksi serentak pada engine database deployment representatif, khususnya reservasi→sewa, bulk billing, approval, reversal, dan token publik.
4. Audit konsistensi notifikasi ulang tahun untuk penghuni `menunggak` serta kebutuhan filter daftar reservasi berdasarkan status.
5. Partial payment/refund dan remaining balance tetap memerlukan definisi aturan bisnis sebelum implementasi.
