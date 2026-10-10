# Checkpoint histori hunian — 9 Oktober 2026

Berangkat dari PR #4 head `54e2990b29f35a56945d83d4183b54c182984106`, tree `cdaa353d0f669e312a8e61173f2a0bfd77291e14`. Retest exact remote 115 tests/771 assertions; CI 37821866312 sukses. Main terakhir `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`. Baca access-recovery, billing-coverage dan night-checkpoint untuk pekerjaan terdahulu. Jangan menduplikasi PR.

## Temuan dan fitur

Dashboard menghitung baris sewa tanpa filter status/kamar unik dan memakai checkout inklusif. Fixture tiga kamar dengan dua sewa berurutan pada kamar pertama, satu menunggak dan satu checkout awal bulan membuat hitungan tidak sesuai occupancy. Tes reproduksi gagal pada baseline. Kini query bersama MonthlyOccupancy menghitung kamar unik dengan overlap [awal, akhir), aktif+selesai di dashboard; menunggak tidak dihitung. Selesai tanpa checkout dan interval kosong/terbalik dikecualikan.

Fitur baru: cakupan `aktif` (default lama) / `riwayat` (aktif+selesai) pada Laporan Hunian. Tahun di luar dropdown lima tahun tetap tercantum ketika dipilih. Teks UI menjelaskan status, checkout eksklusif, basis inventaris saat ini dan bukan rata-rata harian. Daftar status kamar tetap aktif. Tidak mengubah data historis.

Acceptance: satu kamar dengan pergantian penghuni hanya satu hitungan; completed muncul di bulan sebenarnya pada mode riwayat; checkout awal bulan tidak masuk bulan baru; pending menunggak, selesai tanpa akhir dan reversed/empty interval tidak menambah hitungan; active tanpa akhir tetap dihitung; pilihan cakupan/tahun dipertahankan; invalid cakupan 422; nol kamar nol persen. Tes gagal sebelumnya kini lulus.

## Pengujian source final

| Kategori | Status | Bukti/batas |
|---|---|---|
| Functional | LULUS | Boundary/status/unique-room/history/invalid-input/open-end/selected-year tests dan GET HTTP active/history dengan data sintetis. |
| User flow & transaction | LULUS lokal | HTTP login → report filter → 12 bulan → invalid scope 422; core navigation/approval/invoice/ledger/retry/reconciliation/CSV/term coverage/access recovery tetap lulus. SQLite; concurrency DB deployment belum diuji. |
| Responsive | BELUM DIUJI | Browser tidak tersedia, install terdahulu invalid archive. Butuh ponsel/tablet/desktop dan visual chart/filter/403. |
| Bug & regression | LULUS | 118 backend/786 assertions, 4 frontend, build, route/view cache, fresh migrate + rollback/remigrate SQLite, diff check. |
| Retest | LULUS | Dashboard/history reproduksi dua kegagalan kini lulus; suite lengkap dan HTTP diuji setelah perubahan. |
| User testing | BELUM DIUJI | Simulasi agen HTTP lulus; visual usability dan UAT pengguna nyata belum dilakukan. |
| Security | LULUS dalam cakupan | Filter cakupan whitelist, tahun integer bounded, otorisasi view sama; full suite auth/role/CSRF/private links/uploads/XSS/CSV lulus. Lockfile tidak berubah sejak audit nihil; bukan pentest penuh. |

SHA/tree final setelah publish/fetch dicatat di PR. PR tetap draft dan belum siap operasional penuh. Tidak ada data nyata, produksi atau pesan penghuni.

## Kelanjutan sekitar 01.12 WIB

Berikutnya: alur link untuk pembayaran/tagihan yang SUDAH ada (terutama hasil bulk), agar penghuni mengirim bukti tanpa membuat tagihan penuh baru dan tanpa mengubah nominal operator. Perlukan keterikatan payment_id, validasi token/paid status, upload atomik, role dan regresi. Remaining balance/cicilan belum dirancang; jangan mengklaim didukung. Backlog lain manual deduplication, historical income matching, reversal/refund, concurrency representatif, browser/UAT. Lanjutkan prioritas berikut selama waktu/runtime memungkinkan.

UAT: buat satu kamar dengan sewa selesai lalu sewa baru pada bulan yang sama; pilih kedua cakupan, cocokkan bulan checkout dan hitungan kamar, baca penjelasan, bandingkan dashboard. Lakukan pada tiga viewport bersama operator; belum dilaksanakan.
