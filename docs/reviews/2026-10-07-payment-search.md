# Pencarian dan rekonsiliasi pembayaran — 7 Oktober 2026

Source awal: `main` pada `c8c10679929963a96813758de64918566d2daccb`. Workflow Application checks untuk source tersebut telah selesai dengan hasil success.

## Kebutuhan dan hasil

Pencarian tabel sebelumnya hanya menyembunyikan baris pada browser; ekspor CSV dan ringkasan tetap memuat semua penghuni dalam filter bulan/status. Operator belum dapat mengekspor pembayaran khusus satu penghuni/kamar atau memisahkan metode pembayaran melalui filter yang sama.

Kini kolom **Penghuni atau kamar** mencari sebagian nama penghuni atau nomor kamar melalui server. Filter **Metode pembayaran** menyediakan Tunai, Transfer, dan E-wallet. Pencarian digabungkan dengan filter bulan dan status; ringkasan serta CSV memakai query yang sama. Ringkasan juga menampilkan nominal lunas dan belum lunas secara terpisah. Status belum lunas tetap mencakup tagihan belum dibayar dan pembayaran menunggu persetujuan sesuai model yang ada, bukan penanda tunggakan atau bukti transfer.

Input pencarian dibatasi 100 karakter dan menggunakan parameter SQL. Karakter `%`, `_`, dan `!` diperlakukan sebagai teks literal. Teks kosong menampilkan seluruh hasil dari filter lain; pencarian `0` tetap bekerja. Form menggunakan grid responsif, label input, dan Reset. Izin lihat pembayaran yang sama tetap diperlukan, dan tindakan ini tidak mengubah transaksi atau mengirim pesan.

## Validasi

- Enam tes fitur baru gagal pada baseline dan lulus setelah implementasi: kombinasi seluruh filter, pencarian nomor kamar, karakter khusus/SQL injection, whitespace/angka nol, ringkasan nominal, serta validasi input daftar/CSV.
- Tes memastikan tautan ekspor membawa seluruh filter, hasil CSV sesuai daftar, dan pencarian tidak mengubah data pembayaran.
- Suite lokal: **39 tes backend, 188 assertions**; seluruhnya lulus tanpa warning/risky test. **4 tes frontend** lulus. Route cache, view cache, dan pemeriksaan whitespace diff berhasil.
- Database SQLite di memori, data sintetis, dan HTTP keluar diblokir pada tes baru. Tidak ada migrasi skema atau perubahan server/database produksi.

Validasi ini mencakup alur HTTP dan render server; tampilan browser pada perangkat nyata serta perilaku pencarian lintas database deployment belum diverifikasi. Prioritas deduplikasi tagihan dan rekonsiliasi invoice/keuangan dari review sebelumnya tetap berlaku.
