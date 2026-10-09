# Checkpoint peringatan antrean file privat — 10 Oktober 2026

## Source dan tujuan

Prioritas dimulai dari draft PR #4 head `a566a9fcad2d6940270ef51ed112ad6947daaae7`, exact tree `c155f0cf86b6b9866e3f2d160914d83570cd8a64`. CI head diperiksa terpisah sebelum publikasi prioritas ini.

Antrean retry sudah mencegah kegagalan penghapusan file hilang diam-diam, tetapi operator tidak mengetahui backlog kecuali menjalankan command server. Peningkatan ini menampilkan peringatan di Dashboard saat antrean tidak kosong.

## Perubahan dan kriteria penerimaan

Dashboard menghitung jumlah file tertunda, percobaan tertinggi, dan waktu antrean tertua dalam satu query agregat. Peringatan menyebut command inspeksi read-only untuk admin server. Path file, konteks internal, dan detail error storage tidak dikirim ke tampilan.

Kriteria penerimaan: antrean kosong tidak menampilkan alarm; dua job menampilkan hitungan dua dan attempts maksimum; command dry-run terlihat; path serta pesan error privat tidak terlihat; layout memakai susunan vertikal pada layar kecil dan horizontal mulai breakpoint medium.

## Validasi

| Kategori | Status | Bukti dan batasan |
|---|---|---|
| Functional | LULUS | Regresi memeriksa kondisi kosong dan dua job, count, max attempts, petunjuk dry-run, serta tidak adanya path/error. Smoke HTTP login/session nyata juga memverifikasi alarm satu job. |
| User flow & transaction | LULUS lokal | Kegagalan delete → queue → login → alarm Dashboard → dry-run/retry tercakup tes service/command dan HTTP dengan data sintetis. Scheduler/storage deployment belum diuji. |
| Responsive | BELUM DIUJI | Markup memakai Bootstrap responsive (`flex-column flex-md-row`, `text-md-end`, `text-break`), tetapi browser ponsel/tablet/desktop tidak tersedia; review markup bukan bukti lulus. |
| Bug & regression | LULUS | 147 tes backend/1.048 asersi, 4 tes frontend, Vite build, cache route/view, audit dependency, dan smoke HTTP lulus. |
| Retest | LULUS | Tes baseline gagal karena alarm belum ada; skenario yang sama lulus setelah implementasi. |
| User testing | BELUM DIUJI | Simulasi agen memastikan pesan memberi tindakan yang jelas; UAT operator manusia belum dilakukan. |
| Security | LULUS dalam cakupan | Tes memastikan nama/path bukti dan pesan error storage tidak muncul pada HTML. Dashboard tetap di balik auth dan izin Dashboard. Bukan pentest penuh. |

Data seluruhnya sintetis. Status tetap **belum siap operasional penuh** sampai responsive browser, UAT manusia, serta concurrency/database/storage deployment representatif tervalidasi.

## Kelanjutan

Prioritas berikut: audit otorisasi objek/unggahan yang tersisa, health-check yang dapat dipantau infrastruktur bila diperlukan, partial payment/refund setelah definisi bisnis, dan rehearsal staging. Jangan merge draft PR sebelum seluruh blocker serta CI head terselesaikan.
