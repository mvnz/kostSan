# Checkpoint pemulihan hak akses — 9 Oktober 2026

Lanjutan dari `8bf5258ce4ad122b92f9b85f54b38a4340cb6c16`, tree `892b509dba1750f4e9024cb1ffc88f29436bbeaf`, draft PR #4. Main tetap `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`. Coverage retest pada SHA remote: 108 tes/706 assertions dan CI 37820865400 sukses. Baca checkpoint coverage dan night sebelumnya untuk perubahan yang sudah selesai; jangan membuat PR duplikat.

## Bukti dan acceptance

Middleware awal mengarahkan semua penolakan HTML ke dashboard. Operator tanpa hak dashboard menerima redirect kembali ke `/` tanpa akhir, termasuk setelah login. Tiga tes reproduksi gagal sebelum perbaikan: tujuan login salah, tujuan penolakan salah, no-view mendapat 302 bukan terminal 403.

AccessLanding memilih hanya route yang memiliki izin view. Login tanpa intended URL memilih menu itu, fallback Profil Akun; intended URL tetap menjalani pemeriksaan middleware. Penolakan HTML menuju menu berizin atau 403 jika tidak ada. JSON tetap 403. Halaman 403 mandiri menyediakan profil, logout dengan CSRF, dan pesan pemulihan; tidak memuat data kos. Seluruh 16 menu view diuji dengan role tunggal dan halaman tujuan masing-masing mengembalikan 200. Create-only tidak dianggap view. Superadmin dan intended URL dipertahankan.

## Validasi setelah perubahan

| Kategori | Status | Bukti/batas |
|---|---|---|
| Functional | LULUS | Login restricted/no-view, intended denied, superadmin; 16 menu, JSON vs HTML, profil/logout. |
| User flow & transaction | LULUS lokal | HTTP nyata cookie/session/CSRF: restricted login → sewas, penolakan → sewas, no-view → profil/403, logout tanpa CSRF 419 dan dengan CSRF sukses. Smoke pembayaran tiga bulan/approval/invoice/ledger/bulk/CSV tetap lulus. DB concurrency deployment belum diuji. |
| Responsive | BELUM DIUJI | Chromium tidak tersedia; install sebelumnya gagal dengan arsip invalid. Perlu tiga viewport termasuk halaman 403 baru. |
| Bug & regression | LULUS | 115 backend/771 assertions, 4 frontend; build dan route/view compile. |
| Retest | LULUS | Ketiga reproduksi awal kini lulus; full suite dan HTTP mengonfirmasi tanpa redirect loop. |
| User testing | BELUM DIUJI | Simulasi agen HTTP restricted/no-view/superadmin lulus; visual usability dan UAT manusia belum tersedia. |
| Security | LULUS dalam cakupan | Penolakan JSON tetap 403; tidak mengubah izin; create-only tidak membuka view; logout CSRF. Full suite auth/role/private docs/link/input/XSS/CSV dan dependency audit turut diperiksa. Bukan pentest penuh. |

Npm/Composer audit tidak menemukan advisory. Seluruh data uji sintetis, tanpa pesan penghuni/produksi. SHA final diuji dicatat pada PR setelah publish/fetch; draft tetap dipertahankan karena browser/UAT belum terverifikasi.

## Kelanjutan sekitar 01.06 WIB

Lanjutkan prioritas independen: statistik dashboard hunian masih menghitung jumlah sewa termasuk draft dan checkout pada hari pertama, berbeda dari laporan yang sudah diperbaiki. Reproduksi dahulu; gunakan kamar unik dan tanggal akhir eksklusif. Setelah itu histori sewa selesai, tagihan existing/remaining balance, deduplikasi manual/cicilan, legacy reconciliation, reversal/refund, DB representatif dan browser/UAT.

UAT disiapkan: login operator tanpa dashboard dengan satu menu; coba URL terlarang dan intended URL; pastikan dapat bekerja pada menu sah. Login akun tanpa view, baca petunjuk 403, buka profil, keluar. Jalankan di ponsel/tablet/desktop bersama pengguna nyata. Belum dilaksanakan.
