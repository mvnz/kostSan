# Batas penyimpanan nominal pembayaran manual

## Kebutuhan sebelum menulis kode

19 Oktober 2026 19.08 WIB: waktu nyata diperiksa, main di-fetch tetap `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`; head #13 `25df24e862b2a4b8c3e90a1132e8b999ca6ae1c0`, CI39242772811 sukses. PR #4–13 draft tanpa komentar/issue baru atau pekerjaan aktif yang terlihat. Worktree bersih, AGENTS.md tidak ditemukan. Baca README main dan working source, checkpoint #12/#13, controller/model/form pembayaran, migrasi nominal, pola tes atomicity dan harness MySQL. Tidak menganggap checkout lama sebagai main terbaru.

Temuan source yang akan direproduksi: form/controller create/update pembayaran manual membatasi jumlah minimal0, tetapi tidak membatasi kapasitas `DECIMAL(12,2)` yang sama pada pembayaran, invoice dan keuangan. Nilai maksimum penyimpanan positif adalah9999999999.99, bukan batas bisnis baru. SQLite tidak menegakkan kapasitas tersebut. Risiko yang belum dianggap fakta produksi: input di atas kapasitas masuk ke jalur penyimpanan MySQL dan menjadi error DB alih-alih validasi yang bisa diperbaiki operator.

Kriteria penerimaan: create/update manual menolak nilai melebihi kapasitas dengan error field jumlah sebelum file/payment/invoice/ledger berubah; nilai0, nominal biasa dan maksimum tetap diterima sesuai aturan lama; maksimum tersimpan persis sampai invoice dan approval retry satu income pada MySQL nyata. Penggantian bukti pada input invalid tidak menghapus bukti lama/meninggalkan file baru. Form memberi batas yang selaras tanpa paragraf UI baru. Test negatif nonnumeric/negative tetap ditolak. Tidak mengubah aturan pembulatan/pecahan, nominal existing, approval, coverage, izin, transaksi, schema atau endpoint publik/generasi tagihan lain. Jalur lain dicatat untuk tinjauan lanjutan, bukan diklaim aman oleh fix sempit ini.

Peningkatan operasional adalah pemulihan kesalahan input sebelum write, bukan fitur terpisah yang ditumpuk di atas bug. PR terpisah berbasis #13; tidak mengubah PR sebelumnya atau merge sebelum gate staging/UAT. Reproduksi dan hasil final dicatat di bawah setelah dijalankan; tidak ada data/notifikasi produksi.
