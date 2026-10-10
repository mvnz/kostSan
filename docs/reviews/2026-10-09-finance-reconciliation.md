# Checkpoint lanjutan Keuangan — 9 Oktober 2026

## Source dan koordinasi

Fetch langsung main: `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`. Draft PR #4: `1ed30f1faa9cd9fe03d3cb0f264ab0ef975b64d2`, CI 37814248181 sukses; tidak ada issue non-PR terbuka. Tidak ada AGENTS.md atau proses pengembangan lain yang terdeteksi. README, konfigurasi, route/middleware, riwayat dan checkpoint terakhir dibaca. Perubahan ini dibangun di atas head PR tersebut; jangan membuat PR duplikat. Branch lokal lama memiliki head berbeda dan tidak digunakan sebagai baseline final.

## Temuan dan implementasi

Reproduksi FinanceAtomicityTest awal: 3 gagal dari 4 tes. INSERT gagal meninggalkan unggahan baru, UPDATE gagal menghapus bukti lama, dan hapus_bukti gagal tetap kehilangan berkas. Keuangan manual kini menggunakan transaksi, row lock untuk mutasi, pembersihan pengganti pada rollback dan penghapusan bukti lama setelah commit. Penghapusan transaksi gagal mempertahankan bukti. Proteksi sumber otomatis tetap berlaku.

Fitur Rekonsiliasi Pembayaran–Keuangan: dua kategori read-only, lunas tanpa ledger dan ledger tidak sesuai nominal/tanggal/jenis/kategori/status. Filter bulan tanggal bayar, fallback periode untuk tanggal kosong, kedua bulan terdampak untuk tanggal pemasukan berbeda; 25 baris per halaman. Tidak mencocokkan/manual backfill histori. Dua izin view wajib: Keuangan dan Data Sewa. Cache private/no-store; output escaped. Acceptance: pending tanpa ledger tidak ditandai; sumber valid tidak muncul; selisih Rp0,01 terdeteksi; histori manual tidak dimutasi; batas bulan tepat; pagination/filter/izin diuji.

Prioritas berikutnya yang diselesaikan: Detail Keuangan sebelumnya mengarahkan role view-only ke Edit dan kemudian ditolak. Reproduksi gagal 302, retest 200. Halaman detail sekarang read-only, bukti privat, tombol edit dan sumber pembayaran mengikuti izin.

## Bukti pengujian

| Kategori | Status | Bukti / batas |
|---|---|---|
| Functional | LULUS dalam cakupan | Kategori rekonsiliasi, selisih pecahan, batas bulan/invalid input, pagination, view-only details, sukses/gagal lifecycle bukti. |
| User flow & transaction | LULUS lokal | HTTP nyata login/session/CSRF, navigasi kamar–penghuni–reservasi–sewa–pembayaran–invoice–keuangan–laporan; approval → invoice lunas → tepat satu ledger → rekonsiliasi kosong → retry. SQLite sintetis. Concurrency database deployment BELUM DIUJI. |
| Responsive | BELUM DIUJI | Playwright launch gagal executable tidak ada; install chromium --only-shell menghasilkan arsip tidak valid. Perlu 375×812, 768×1024, 1440×900 dengan browser. Build bukan validasi UI. |
| Bug & regression | LULUS | 83 backend / 479 assertions; 4 frontend tests; Vite build, fresh SQLite migrate, rollback/remigrate, route/view compile, diff check. |
| Retest | LULUS | Semua reproduksi FinanceAtomicityTest dan read-only Detail lulus pada source akhir; proteksi otomatis dan suite invoice/payment ikut lulus. |
| User testing | BELUM DIUJI | Simulasi agen via HTTP lulus. Visual usability dan UAT manusia belum dilakukan; jangan menyamakan otomatisasi dengan pengguna nyata. |
| Security | LULUS dalam cakupan | Login dan CSRF 419, dual-role report denial, view-only mutation denial, escaped XSS output, no-store, upload/private document/public token suite; audit Composer locked lokal nihil advisori. Bukan pentest penuh. |

Composer phar validate strict dan platform requirements lulus. Composer distro tidak kompatibel runtime (Normalizer), gunakan `php ../composer.phar`; tidak mengubah dependency untuk masalah tooling. Data seluruhnya sintetis, database baru terisolasi. Tidak ada produksi atau pesan penghuni. SHA/tree final dan hasil CI dicatat pada PR setelah upload/verifikasi.

## Kelanjutan malam dan UAT

Waktu checkpoint sekitar 00.32 WIB, masih sebelum 05.00; lanjutkan prioritas independen berikutnya setelah penyimpanan dan validasi, jangan menganggap program malam selesai. Prioritas: export Keuangan yang masih tombol tanpa aksi; deduplikasi pembayaran multi-bulan; reversal/refund yang menjaga histori; validasi responsive; concurrency database deployment.

Skenario UAT: admin buka Keuangan → Rekonsiliasi, pilih bulan, pindah kategori dan halaman, cocokkan bukti manual tanpa membuat duplikat; role Keuangan-only tidak melihat link rekonsiliasi atau pembayaran; role view-only membuka Detail tanpa diarahkan ke Edit; ulangi pada tiga viewport. UAT manusia belum dilaksanakan. Tetap draft karena responsive/usability belum diverifikasi.
