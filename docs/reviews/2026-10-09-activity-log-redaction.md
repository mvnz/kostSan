# Checkpoint redaksi token log aktivitas — 9 Oktober 2026

## Source dan temuan

Prioritas dimulai dari draft PR #4 head `29554d3b1013206fc4be5d85bb7f7a98d1b3e56f`, tree `5550ad151f6898dd436d26eb859b9457fb3763d0`. Main yang diperiksa tetap `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`.

Middleware `LogActivity` menyimpan `fullUrl()` untuk semua mutasi. Reproduksi POST pendaftaran yang gagal validasi membuktikan token link publik tersimpan utuh pada kolom URL sementara link belum digunakan dan masih aktif. Pengguna yang dapat membaca log atau salinan database dengan demikian dapat memperoleh tautan yang masih sah.

## Perubahan dan manfaat

URL audit sekarang dibentuk tanpa query string. Parameter route bernama `token`, `signature`, `secret`, atau `key` (pencocokan tidak peka huruf besar/kecil) diganti dengan `[REDACTED]`, termasuk bentuk URL-encoded. Nama route, method, status, dan segmen non-sensitif tetap tersedia untuk penelusuran operasional. Panjang URL juga dibatasi sesuai ukuran kolom.

Kriteria penerimaan: token pendaftaran aktif tidak muncul di log saat validasi gagal; token pembayaran acak tidak muncul; placeholder redaksi terlihat; query string sensitif tidak tersimpan; token tetap belum dipakai; alur log biasa tidak rusak.

## Validasi

| Kategori | Status | Bukti dan batasan |
|---|---|---|
| Functional | LULUS | Tiga regresi khusus memeriksa token pendaftaran, token pembayaran, dan query string mutasi. Placeholder, status gagal, serta token pendaftaran yang tetap unused diverifikasi. |
| User flow & transaction | LULUS lokal | Smoke HTTP memakai session dan CSRF nyata: submit publik gagal validasi membuat audit berguna tanpa mengonsumsi link atau membocorkan token. Alur operasional lengkap ikut suite final. Concurrency deployment belum diuji. |
| Responsive | BELUM DIUJI | Tidak ada UI baru; tampilan aplikasi pada ponsel/tablet/desktop tetap memerlukan browser nyata. |
| Bug & regression | LULUS | Reproduksi gagal sebelum perbaikan dan lulus sesudahnya; 146 tes backend/1.039 asersi, 4 tes frontend, build Vite, cache route/view, migrasi maju–mundur–maju, serta audit Composer/npm lulus. |
| Retest | LULUS | Skenario kebocoran asli diulang pada kode final dan nilai token tidak ditemukan pada URL log. |
| User testing | BELUM DIUJI | Simulasi agen atas error form publik lulus; belum ada UAT manusia. |
| Security | LULUS dalam lingkup uji | Dua kelas token publik dan query secret tertutup; link aktif tetap unused. Review ini bukan pentest penuh dan akses database/log deployment belum diverifikasi. |

Seluruh data tes sintetis. Status aplikasi tetap **belum siap operasional penuh** sampai pengujian responsive browser, UAT manusia, dan database/storage deployment representatif selesai.

## Kelanjutan

Prioritas berikut: tampilkan/monitor antrean cleanup yang menumpuk, audit otorisasi objek dan unggahan yang tersisa, serta concurrency database deployment. Jangan merge draft PR #4 sebelum gap wajib dan CI head terbaru selesai.
