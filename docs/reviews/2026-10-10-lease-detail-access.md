# Checkpoint detail sewa read-only — 10 Oktober 2026

## Source dan temuan

Prioritas dimulai dari draft PR #4 head `3d58725b8246e531f911c5c6559c0bca75d1758e`, exact tree `2b5c1e56a03f30fa2cedb024761355769cde44b4`.

Detail Sewa dapat diakses role view-only, tetapi selalu merender tombol Edit/Hapus yang kemudian ditolak middleware. Bukti sewa hanya dapat ditelusuri dari form edit, sehingga operator read-only tidak memiliki alur sah untuk memverifikasi dokumen meskipun punya hak lihat.

## Perubahan dan manfaat

Tombol mutasi pada Detail Sewa kini mengikuti hak update/delete. Bukti ditampilkan sebagai link ke endpoint `secure-files` yang sudah memeriksa hak **Lihat Data Sewa** dan mengirim `Cache-Control: no-store, private`. Pengguna tanpa hak lihat diarahkan dari detail dan mendapat 403 saat meminta file langsung.

Kriteria penerimaan: viewer membuka detail dan bukti privat; viewer tidak melihat URL edit/form delete serta PUT/DELETE JSON mendapat 403; role tanpa view tidak dapat membuka detail/file; superadmin dan guard histori tetap bekerja.

## Validasi

| Kategori | Status | Bukti dan batasan |
|---|---|---|
| Functional | LULUS | Dua regresi role mencakup viewer dan tanpa akses; detail, link bukti, header no-store, redirect/403, dan mutation denial diverifikasi. |
| User flow & transaction | LULUS lokal | Viewer login → Detail Sewa → bukti privat berhasil; edit/delete ditolak. Data sintetis, tidak ada mutasi bisnis. |
| Responsive | BELUM DIUJI | Link dan tombol memakai komponen existing; browser ponsel/tablet/desktop tidak tersedia. |
| Bug & regression | LULUS | Suite final, build, cache, dan audit dicatat pada commit/PR. |
| Retest | LULUS | UI viewer sebelumnya menawarkan mutasi dan tidak memberi link bukti; skenario identik kini sesuai permission. |
| User testing | BELUM DIUJI | Simulasi agen viewer menunjukkan alur traceability jelas; UAT operator manusia belum dilakukan. |
| Security | LULUS dalam cakupan | UI least-privilege, middleware mutation 403, controller file 403, dan no-store diuji. Bukan pentest penuh. |

Status tetap **belum siap operasional penuh** hingga responsive browser, UAT manusia, serta concurrency/database/storage deployment representatif tervalidasi.

## Kelanjutan

Audit tampilan detail lain terhadap hak create/update/delete, lalu validasi responsive dan UAT pada staging. Hindari memperluas partial payment/refund sebelum aturan bisnis ditetapkan.
