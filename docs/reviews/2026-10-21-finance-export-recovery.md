# Pemulihan URL ekspor Keuangan invalid

## Kebutuhan sebelum implementasi

21 Oktober 2026 16.02 WIB, waktu nyata diperiksa. Main terbaru yang benar-benar ada tetap `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`; head draft PR #16 `11f500a24213166a65113edea2679522e703cdb0` dan ketiga job CI 39275247081 sukses. PR #4–16 masih draft tanpa issue/komentar/perubahan baru atau pekerjaan aktif yang terlihat. Worktree bersih dan AGENTS.md tidak ditemukan. Karena ekspor Keuangan belum ada di main, perubahan kecil ini berbasis head PR #16 dan akan dipublikasikan sebagai PR dependensi tersendiri, bukan dicampur ke commit PR lama.

Gap yang akan direproduksi: `KeuanganController::export()` memvalidasi query GET dengan `$request->validate()`. Untuk request HTML, tujuan redirect default dapat berasal dari Referer/previous URL yang invalid itu sendiri. Tes existing hanya membuktikan JSON 422, sehingga belum membuktikan browser kembali ke halaman yang dapat dipakai dan tidak mengunduh seluruh buku tanpa filter. Pola masalah yang sama sudah terbukti dan diperbaiki untuk pembayaran di PR #16; ekspor Keuangan adalah endpoint terpisah dan belum tercakup.

Manfaat operasional: bookmark, tautan lama, atau query bulan/jenis yang rusak berhenti pada daftar Keuangan bersih dengan pesan yang bisa dipulihkan, tanpa risiko operator mengira ekspor tanpa filter sebagai hasil filter. Scope hanya validasi query ekspor Keuangan, tes regresi, browser tiga viewport, dan dokumentasi. Tidak mengubah query valid, isi CSV, nominal, permission, transaksi, schema, file bukti, atau UI filter daftar yang berjalan di sisi klien.

Kriteria penerimaan: HTML GET export dengan bulan/jenis invalid (termasuk array) selalu redirect ke named route daftar Keuangan tanpa query, menyimpan field error, dan halaman tujuan 200. Request JSON tetap 422. Guest tetap diarahkan login dan role tanpa izin tetap ditolak sebelum ekspor/validasi. Tidak ada CSV atau mutasi record pada input invalid. Setelah pemulihan, bulan dan jenis valid harus menghasilkan CSV yang cocok dengan baris tabel pada fixture sintetis. Browser menjalankan skenario pada ponsel, tablet, dan desktop serta memeriksa aset/overflow/no-download. Tes backend harus gagal pada source sebelum fix lalu lulus sesudah fix. UAT manusia, staging, serta database produksi tetap di luar scope dan tidak akan diklaim selesai.

## Reproduksi dan perubahan

Belum dijalankan. Bagian ini akan diisi dengan SHA/tree test-only, expected/actual kegagalan, implementasi final, dan self-review diff.

## Validasi dan kelanjutan

Belum dijalankan. Bagian ini akan diisi dengan source final yang diuji, semua perintah/hasil, tujuh kategori pengujian, PR/CI, batasan, dan prioritas berikutnya.
