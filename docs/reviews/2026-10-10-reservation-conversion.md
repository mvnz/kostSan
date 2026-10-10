# Checkpoint: konversi reservasi menjadi sewa

Tanggal: 10 Oktober 2026  
Basis awal: `809eba21291266e74530d0ae5a95f8297b4eda0e` (head PR #4 sebelum prioritas ini)

## Temuan dan reproduksi

`RoomAvailability` sebelumnya mengecualikan seluruh reservasi terkonfirmasi milik penghuni yang sama ketika sewa dibuat. Ini membuat sewa biasa dapat melewati reservasi tanpa identitas konversi. Reservasi tetap berstatus `dikonfirmasi`, tetap muncul sebagai komitmen kamar, dan tidak mempunyai tautan ke sewa yang terbentuk.

## Perbaikan dan fitur operasional

- Reservasi terkonfirmasi mempunyai aksi **Jadikan Sewa** untuk operator dengan hak buat Data Sewa.
- Form sewa memuat kamar, penghuni, dan periode reservasi serta mengirim ID reservasi tersembunyi.
- Backend mengunci kamar dan reservasi, memverifikasi status/kecocokan kamar-penghuni, memeriksa benturan dengan pengecualian hanya untuk reservasi yang dipilih, lalu membuat sewa dan menutup reservasi sebagai `dikonversi` dalam satu transaksi.
- Migrasi menambahkan `reservasis.sewa_id` yang nullable, unik, dan foreign key restriktif. Reservasi dan sewa saling menaut pada halaman detail.
- Reservasi hasil konversi tidak dapat diubah/dihapus; sewa hasil konversi tidak dapat dihapus. Riwayat tetap dapat ditelusuri.
- Jalur membuat sewa biasa kini tidak lagi mengecualikan reservasi hanya karena penghuni sama.

Kriteria penerimaan: form terisi dari reservasi; konversi valid menghasilkan tepat satu sewa dan menutup reservasi; manipulasi penghuni/kamar ditolak; retry tidak menggandakan; kegagalan penutupan reservasi me-rollback sewa/status kamar; jalur non-konversi tetap menolak benturan; riwayat hasil konversi immutable.

## Bukti pengujian prioritas

- `ReservationConversionTest`: 6 skenario konversi, manipulasi, rollback trigger, bypass penghuni sama, retry, dan immutability lulus.
- Regresi reservasi, integritas operasional, dan lifecycle bukti sewa lulus.
- Suite backend final prioritas: **186 tes / 1302 assertion**, lulus.
- Frontend calendar: **4 tes**, lulus; Vite production build lulus.
- SQLite terisolasi: `migrate:fresh`, rollback migrasi terbaru, lalu migrate ulang lulus. Pengujian ini juga menemukan dan memperbaiki rollback awal yang meninggalkan indeks unik.

## Tujuh kategori

| Kategori | Status | Bukti/batasan |
|---|---|---|
| Functional | LULUS | Input valid, manipulasi relasi, retry, status akhir, tautan dua arah, dan pembatasan hapus diuji. |
| User flow & transaction | LULUS lokal | Reservasi → form sewa → commit sewa/status kamar/status reservasi; trigger kegagalan membuktikan rollback. Persaingan nyata engine deployment belum diuji. |
| Responsive | BELUM DIUJI | Build lulus, tetapi tidak tersedia browser lokal untuk verifikasi viewport ponsel/tablet/desktop. |
| Bug & regression | LULUS | Suite backend dan frontend penuh lulus setelah perubahan. |
| Retest | LULUS | Reproduksi bypass penghuni sama kini ditolak; rollback migrasi yang sempat gagal diperbaiki lalu lulus. |
| User testing | BELUM DIUJI | Simulasi agen melalui HTTP lulus; UAT manusia nyata belum dilakukan. |
| Security | LULUS dalam lingkup | Manipulasi ID penghuni, konversi ulang, update/delete histori, auth/permission melalui regresi diuji. Bukan pentest deployment. |

## Prioritas berikutnya

Audit notifikasi ulang tahun untuk penghuni menunggak dan konsistensi status reservasi kedaluwarsa; lalu uji responsive/UAT serta transaksi serentak pada database deployment representatif.
