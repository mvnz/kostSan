# Checkpoint link bukti tagihan existing — 9 Oktober 2026

Berangkat dari PR #4 head `3fe3c88516d98d1c6d3f48ff21bd367a7054e812`, tree `a0c86a2e2398735fb4ad178b8dcecc8e496f1ec4`; exact remote 118 tests/786 assertions dan CI 37825202695 sukses. Main terakhir `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`. Baca checkpoint occupancy/access/billing/night sebelumnya; jangan mengulang atau membuat PR baru.

## Kekurangan dan implementasi

Tagihan bulk/manual sudah menghasilkan Pembayaran pending, tetapi link publik lama selalu mencoba membuat tagihan seluruh term baru dan ditolak oleh perlindungan overlap. Tidak ada jalur penghuni mengirim bukti ke tagihan yang sudah ada. Fitur baru mengikat SewaPaymentLink opsional ke payment_id. Dari Detail Pembayaran pending, operator berizin update membuat link existing-bill tujuh hari; link lama pending untuk payment yang sama direvoke dalam transaksi.

Form publik menampilkan penghuni, kamar, periode dan nominal existing. Server hanya menerima metode, catatan dan berkas; payload nominal/periode/status/sewa diabaikan. Transfer tanpa bukti baru/lama ditolak, tunai boleh tanpa berkas. Submit mengunci link, sewa dan payment; status harus pending dan ownership harus cocok. Payment/invoice diperbarui dalam transaksi, tetap pending/draft sampai approval. Bukti baru dibersihkan saat rollback; bukti lama dihapus setelah commit. Token tidak habis saat validasi/storage/database gagal. Paid/deleted/revoked/expired link tidak dapat dipakai. Link menggunakan permission update, bukan view.

Acceptance teruji: generate/revoke; role viewer 403; paid generation 422; stale paid link expired; tampilan period/decimal; payload spoof ditolak secara efektif; satu payment/invoice; transfer proof required; cash valid; old proof/unused token bertahan saat invoice-trigger rollback; upload replacement dibersihkan; second use expired.

## Pengujian final

| Kategori | Status | Bukti/batas |
|---|---|---|
| Functional | LULUS | 5 tes/55 assertions fitur: valid/invalid/boundary/paid/revoked/permission/spoof/upload. Full suite 123/841. |
| User flow & transaction | LULUS lokal | HTTP admin → detail → generate link → public form → submit → satu payment/invoice draft → second GET expired. Nominal/periode konsisten. Core room/resident/reservation/lease/term payment/approve/invoice/ledger/reconciliation/CSV/access/history smoke tetap lulus. SQLite; concurrency DB deployment belum diuji. |
| Responsive | BELUM DIUJI | Browser executable tidak tersedia; install terdahulu menghasilkan arsip invalid. Perlu form/link/detail pada ponsel, tablet, desktop. |
| Bug & regression | LULUS | 123 backend/841 assertions, 4 frontend, Vite build, route/view cache, migration fresh + rollback/remigrate, diff/format. |
| Retest | LULUS | Alur existing-bill yang sebelumnya tidak tersedia kini selesai tanpa duplikasi; rollback trigger dan storage failure diulang; full suite/HTTP lulus. |
| User testing | BELUM DIUJI | Simulasi agen operator/penghuni/pemilik via HTTP lulus; visual usability dan UAT manusia belum dilakukan. |
| Security | LULUS dalam cakupan | Auth/update permission, token one-use/revoke/expiry, object relation, server-owned fields, CSRF, private upload, rollback, output escaped; secret scan dan Composer/npm audit nihil. Bukan pentest penuh. |

Data sintetis. Tidak ada produksi/pesan penghuni. SHA/tree final dicatat di PR setelah publish/fetch. Draft tetap belum siap operasional penuh karena responsive/UAT dan DB concurrency belum terverifikasi.

## Kelanjutan sekitar 01.37 WIB

Prioritas berikut: koreksi/reversal/refund yang mempertahankan pembayaran, invoice dan ledger asli; hindari edit/delete transaksi lunas. Setelah itu legacy reconciliation/manual matching, deduplikasi manual dengan definisi cicilan, concurrency representatif, browser responsive dan UAT. Remaining balance setelah perpanjangan belum dirancang; gunakan monthly bulk/existing-bill link.

UAT: operator buat tagihan bulk, buka Detail dan buat link; penghuni coba transfer tanpa bukti (harus jelas), upload valid, coba ulang link; pemilik verifikasi bukti privat lalu approve, periksa invoice/Keuangan. Ulangi cash, paid/revoked link, ponsel/tablet/desktop. Belum dilakukan dengan pengguna nyata.
