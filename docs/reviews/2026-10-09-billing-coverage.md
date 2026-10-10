# Checkpoint cakupan tagihan — 9 Oktober 2026

Baca juga `2026-10-09-night-checkpoint.md`: sepuluh prioritas sebelumnya sudah tersimpan di draft PR #4 pada `fdf9a0ac1476cb093d1f714bef155bec6dca2dff`; CI 37819633846 sukses. Main yang di-fetch tetap `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`. Lanjutan ini berangkat dari head remote itu. Jangan membuat PR duplikat atau mengulang perbaikan/export/detail yang selesai.

## Reproduksi dan perubahan

Tes end-to-end pada controller baseline fdf9a0a, dalam SQLite terisolasi, membuat pembayaran seluruh term tiga bulan lewat link, approve, kemudian bulk tiga bulan. Ditemukan 3 pembayaran ketika seharusnya 1: dua tagihan bulk tambahan. Setelah perubahan, tinggal 1 pembayaran, 1 invoice, 1 pemasukan.

Migrasi menambahkan coverage_start/end nullable dan index per sewa. Hanya pembayaran baru melalui link mengisi snapshot awal/akhir term; tanggal akhir eksklusif. Bulk mengenali overlap dengan snapshot sehingga seluruh bulan yang tercakup dilewati, termasuk pending. Legacy tanpa cakupan mempertahankan fallback bulan periode; tidak menebak cakupan dari nominal.

Link lain untuk sewa yang sama ditolak bila masa term sudah memiliki tagihan. Check dan create dilakukan setelah row lock sewa, bersama token lock dan transaksi; penolakan tidak menghabiskan token atau menyimpan unggahan. Snapshot berasal dari sewa, tidak dapat dipalsukan melalui payload publik. Masa kosong/terbalik ditolak. Detail Pembayaran menampilkan masa yang ditagih. Memindahkan sewa/periode pembayaran dengan snapshot ditolak sebelum unggahan atau update; pending dapat dihapus/dibuat ulang.

Acceptance: term tiga bulan mencegah bulk ketiga bulan; link kedua ditolak; token tetap unused; no orphan upload; perubahan tanggal keluar sewa tidak memperluas snapshot; setelah extension, bulan sesudah coverage_end dapat ditagih; existing manual bill di term mencegah link penuh tambahan; invalid interval ditolak; fresh/rollback/remigrate lulus. Retest menemukan perbandingan tanggal SQLite menyertakan jam pada boundary checkout; diperbaiki dengan whereDate dan semua tes kembali lulus.

## Pengujian final

| Kategori | Status | Bukti dan batas |
|---|---|---|
| Functional | LULUS dalam cakupan | Snapshot, overlap bulan, batas checkout, extension, invalid interval, perlindungan periode, payload publik dan legacy fallback. |
| User flow & transaction | LULUS lokal | HTTP session/CSRF → link tiga bulan → link kedua 422 → bulk semua bulan → tetap satu payment; token kedua unused. Suite lengkap registration/approval/invoice/ledger/export/detail turut lulus. Dua penulis serentak pada DB deployment BELUM DIUJI. |
| Responsive | BELUM DIUJI | Browser executable tidak ada, install menghasilkan arsip invalid; perlu tiga viewport dan klik form/link/filter. |
| Bug & regression | LULUS | 108 backend / 706 assertions, 4 frontend, build, route/view compile, migration + rollback/remigrate, diff check. |
| Retest | LULUS | Skenario baseline 3 pembayaran kini 1; batas checkout/extension yang gagal saat implementasi kini lulus, lalu full suite. |
| User testing | BELUM DIUJI | Simulasi HTTP agen lulus; visual usability dan UAT manusia belum dilakukan. |
| Security | LULUS dalam cakupan | Snapshot server-side, validasi period/method, token retry/used protections, no orphan upload; suite auth/role/CSRF/private documents/XSS/CSV. Dependency lock unchanged from audited head; Composer/npm audit nihil pada pelaksanaan malam. Bukan pentest penuh. |

Data seluruhnya sintetis. Tidak ada produksi atau pesan penghuni. SHA/tree final diverifikasi melalui fetch remote dan dicatat pada PR. PR tetap draft; status belum siap operasional penuh.

## Kelanjutan

Waktu checkpoint sekitar 00.58 WIB. Lanjutkan berikutnya dari PR/head/checkpoint ini dalam rangkaian sampai penutupan 05.00; jangan mengakhiri program malam karena satu prioritas selesai. Periksa eksekusi aktif dan CI sebelum menulis.

Deduplikasi belum menyeluruh: manual masih bisa membuat pembayaran ganda, legacy multi-bulan tidak memiliki coverage, dan cicilan belum didefinisikan. Link setelah extension belum menghitung hanya saldo term: ia menolak overlap alih-alih menggandakan seluruh term; gunakan tagihan bulanan untuk sisa masa sampai alur remaining balance dirancang. Prioritas berikut: kebijakan/metadata untuk manual dan cicilan yang sah; saldo tagihan tersisa/tautan ke tagihan existing; legacy reconciliation; reversal/refund dengan histori; DB concurrency; browser responsive dan UAT. Jangan mengklaim seluruh deduplikasi selesai.

Skenario UAT: buat term 3 bulan, bayar via link, buka Detail dan verifikasi masa, bulk tiga bulan tanpa tagihan baru, coba link kedua dan baca pesan pemulihan; extend term lalu bulk bulan baru; lakukan dengan admin/operator dan layar ponsel/tablet/desktop. UAT manusia belum dilaksanakan.
