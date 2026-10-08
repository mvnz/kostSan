# Checkpoint pembalikan pembayaran — 9 Oktober 2026

Berangkat dari draft PR #4 head `3be70ba77ecf827b6672dd13a9afa7719babbd9d`, tree `2cd1e2fba989b7ec0f9c3a9fdac6260a4ad917f9`; exact remote 123 tests/841 assertions. CI head masih dipantau saat checkpoint. Main terakhir `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`. Baca checkpoint existing-bill/occupancy/access/billing/night sebelumnya; jangan duplikasi PR.

## Temuan dan implementasi

Pembayaran lunas tidak dapat diedit/dihapus—benar untuk audit—tetapi tidak ada koreksi jika dana dibatalkan/dikembalikan. Implementasi baru menyimpan satu PaymentReversal per pembayaran, menghubungkan pemasukan asli, pengeluaran penyeimbang, pengguna, tanggal dan alasan. Pembayaran/invoice/pemasukan asli tetap utuh; laporan saldo menghitung offset. Tidak mengubah status historis lunas; detail menandainya dibalik penuh.

Controller mengunci payment dan pemasukan, memerlukan status lunas, ledger asal yang cocok pada tanggal/nominal/jenis/kategori, alasan 3–500 karakter, serta tanggal dari tanggal bayar sampai hari lokal ini. Satu transaksi membuat offset + reversal; retry idempotent. FK/unique melindungi satu reversal dan histori. Offset dikenali bukan manual, sehingga edit/delete ditolak; detail/index/CSV menautkan payment. Akses memakai izin update Data Sewa. Default aplikasi diubah dari UTC ke `APP_TIMEZONE=Asia/Jakarta`, penting untuk tanggal operasional malam; dapat dioverride saat deployment.

Acceptance: full reversal → satu income + satu expense sama, saldo nol, invoice/payment asli tetap lunas; second request tidak menggandakan; invalid/future/pre-payment date, short reason, pending, missing/mismatch ledger ditolak; role viewer 403; trigger failure rollback offset dan retry sukses; reversal ledger immutable; alasan escaped; CSV source/ID benar.

## Pengujian final

| Kategori | Status | Bukti/batas |
|---|---|---|
| Functional | LULUS | 5 tes/60 assertions reversal, seluruh valid/invalid/boundary/state/reconciliation/immutability/output. Full suite 128/901. |
| User flow & transaction | LULUS lokal | HTTP existing bill → bukti → approve → CSRF reversal 419 → valid reversal dua kali → tetap satu income+offset, invoice/history retained. Core flow lainnya tetap lulus. SQLite; writer concurrency deployment belum diuji. |
| Responsive | BELUM DIUJI | Browser executable tidak tersedia; perlu form pembalikan/detail/table pada ponsel/tablet/desktop. |
| Bug & regression | LULUS | 128 backend/901 assertions, 4 frontend, Vite build, route/view cache, fresh migration + rollback/remigrate, formatter/diff, dependency checks. |
| Retest | LULUS | Trigger rollback menyisakan satu income/no reversal, kemudian retry sukses; idempotensi/validation/full suite/HTTP diulang setelah perbaikan. |
| User testing | BELUM DIUJI | Simulasi agen admin/operator/penghuni via HTTP lulus; UAT manusia dan visual usability belum dilakukan. |
| Security | LULUS dalam cakupan | Auth/update role, CSRF, locks/unique/FK, server-owned amount, bounded reason/date, escaped output, immutable offset, secret scan/audits nihil. Bukan pentest penuh. |

Tidak ada data nyata, produksi, atau pesan penghuni. SHA/tree final dicatat setelah publish/fetch. PR tetap draft, belum siap operasi penuh sebelum browser/UAT dan concurrency representatif.

## Kelanjutan sekitar 01.42 WIB

Prioritas berikutnya: deduplikasi tagihan manual dengan definisi eksplisit—blok overlap default tetapi sediakan alasan/metadata cicilan yang sah, tanpa menganggap semua nominal parsial duplikat. Lalu legacy manual-income matching, partial refund bila kebutuhan bisnis terdefinisi, deployment DB concurrency, browser responsive/UAT.

UAT: approve tagihan sintetis, periksa income/invoice; coba tanggal sebelum bayar dan alasan kosong; lakukan full reversal; verifikasi payment/invoice tetap ada, saldo net nol, tombol hilang, retry tidak menambah transaksi, offset tak bisa diedit. Jalankan tiga viewport dengan pemilik/operator nyata. Belum dilaksanakan.
