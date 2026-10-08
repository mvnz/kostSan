# Review pembayaran dan rekonsiliasi — 8 Oktober 2026

## Baseline dan koordinasi

Baseline GitHub `main`: `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`, tree `849ce8bcd2614b2b66f155a59da9c3135b720526`. Checkout lokal dibandingkan dengan tree remote dan identik sebelum perubahan. CI baseline https://github.com/mvnz/kostSan/actions/runs/37650554079 selesai sukses. Tidak ada AGENTS.md pada source, PR/issue terbuka, atau ruleset; main tidak diproteksi pada pemeriksaan. Riwayat dan seluruh catatan review sebelumnya dibaca. Ini eksekusi manual pagi, bukan klaim proses semalam berjalan nonstop.

## Bukti reproduksi dan perbaikan

Tiga tes reproduksi awal gagal pada baseline:
1. Kegagalan INSERT invoice lewat trigger sintetis meninggalkan satu pembayaran manual tersimpan.
2. Kegagalan UPDATE invoice membatalkan perubahan database tetapi bukti pembayaran lama sudah dihapus.
3. Penghapusan pembayaran pending meninggalkan bukti pembayaran pada disk privat.

Pembuatan manual kini memakai transaksi pembayaran+invoice dengan pembersihan berkas baru saat gagal. Penggantian/penghapusan bukti lama memakai callback pascacommit. Retest membuktikan rollback mempertahankan bukti lama dan membersihkan pengganti; penghapusan gagal mempertahankan pembayaran/invoice/bukti; penggantian dan penghapusan sukses membersihkan bukti lama. Pembayaran lunas tetap menolak kedua tindakan sebelum berkas pengganti disimpan. Lingkup perbaikan siklus berkas adalah controller pembayaran manual, belum controller Keuangan/publik.

## Fitur operasional baru

**Invoice → Rekonsiliasi Invoice** menampilkan tiga kategori, jumlah temuan, dan pagination 25 baris: AUTO legacy tanpa relasi, pembayaran tanpa invoice, serta perbedaan penghuni/periode/nominal/status terhadap pembayaran asal. Laporan read-only, no-store/private, wajib izin lihat invoice, tidak memasangkan data legacy secara spekulatif, dan mengecualikan invoice manual biasa. Status terkirim pada pembayaran pending valid. Pecahan nominal dibandingkan tanpa pembulatan ke rupiah bulat. Belum ada rekonsiliasi buku Keuangan atau tindakan perbaikan massal.

## Tujuh kategori pengujian

| Kategori | Status | Bukti dan batas |
|---|---|---|
| Functional | LULUS | Klasifikasi tiga kategori, invalid kategori 422, empty-state, pagination 25/1, nominal pecahan dan setiap jenis mismatch; dalam lingkup perubahan. |
| User flow & transaction | LULUS | HTTP session/login/CSRF → daftar invoice → laporan/kategori/halaman kedua; suite alur operasional lama; trigger kegagalan insert/update/delete, rollback dan bukti privat. Concurrency database deployment belum diuji. |
| Responsive | BELUM DIUJI | Playwright library tersedia tetapi executable Chromium tidak terpasang; build/kompilasi bukan bukti responsif. Perlu browser ponsel/tablet/desktop. |
| Bug & regression | LULUS | 64 backend tests / 336 assertions; 4 frontend tests; Vite build; route dan view compilation; git diff --check. |
| Retest | LULUS | Tiga reproduksi awal kini lulus; tes tambahan kegagalan delete dan larangan edit/delete pembayaran lunas juga lulus. |
| User testing | BELUM DIUJI | Simulasi operator lewat HTTP berhasil, tetapi evaluasi visual/usability dan UAT pengguna nyata belum tersedia. Skenario UAT disediakan di bawah. |
| Security | LULUS | Dalam lingkup tes: auth redirect, role denial 403, validation 422, escaped script payload, no-store/private, CSRF 419, suite login/injection/link/upload/traversal. Audit dependency lokal timeout Packagist; status advisori terbaru harus dibuktikan CI. Bukan pentest penuh. |

Migrasi seluruh skema pada SQLite baru dan data sintetis berhasil. Composer validate --strict dan platform requirements lulus. Tidak ada migrasi baru pada perubahan ini. Tes lokal terakhir dijalankan setelah perubahan kode terakhir. Dokumen ini dan README tidak mengubah executable. SHA/tree final dan tautan CI tersedia pada commit/PR yang memuat catatan ini; verifikasi tree PR sebelum integrasi.

## Skenario UAT dan responsive untuk pelaksanaan berikutnya

1. Login admin/operator berizin invoice; buka Invoice dan tekan Rekonsiliasi Invoice. Semua kategori dan jumlah terbaca.
2. Di 375×812, 768×1024, 1440×900, pilih tiap kategori dan navigasikan halaman kedua/kembali; periksa wrapping tombol, scroll tabel, sidebar, keterbacaan nomor invoice/nominal dan tidak ada halaman overflow tak terkendali.
3. Dengan pengguna hanya berizin lihat invoice, buka laporan tanpa menyediakan aksi ubah; pengguna tanpa izin ditolak. Pengguna logout diarahkan ke login.
4. Cocokkan satu invoice legacy dengan bukti transaksi sintetis, tanpa mengubah data laporan. Operator harus memahami bahwa data legacy perlu pemeriksaan manusia dan buku Keuangan belum tercakup.
5. Catat keberhasilan tugas, waktu, kesalahan/kebingungan, ukuran viewport/browser, dan identitas peran penguji. UAT pengguna nyata hanya dicatat selesai setelah partisipasi/bukti nyata.

## Checkpoint

Status: **belum siap operasional penuh**. Simpan perubahan sebagai draft PR karena responsive dan UAT belum terverifikasi. Jangan menumpuk PR kedua untuk scope ini; lanjutkan draft yang sama. Prioritas berikutnya: browser responsive/usability, audit CI, verifikasi tree dan integrasi jika memenuhi pemeriksaan; kemudian hubungan pembayaran–buku Keuangan, deduplikasi multi-bulan, invoice legacy ambigu, concurrency database deployment, dan callback cleanup/file lifecycle di controller lain. Kegagalan storage cleanup pascacommit tidak dapat memutar balik transaksi database; belum ada durable cleanup queue untuk pemulihan setelah crash.

Seluruh data sintetis; tidak ada perubahan server/database produksi atau notifikasi penghuni.
