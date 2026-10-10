# Checkpoint antrean pembersihan file privat — 9 Oktober 2026

## Source dan temuan

Prioritas dimulai dari draft PR #4 head `d3c307583645fc44a6d6eeb2b75e33ad8054d03b`, tree teruji `29c1983c7bda0800043ea5372bc196cc8f999894`; CI run `37958710914` sukses. Main yang diperiksa tetap `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`.

Controller sudah menunda penghapusan bukti lama sampai transaksi database commit—benar untuk integritas transaksi—tetapi hasil `Storage::delete()` diabaikan. Bila storage offline atau delete mengembalikan false, file menjadi orphan tanpa record/retry. Tes baseline fitur baru membuktikan kegagalan delete belum menghasilkan antrean dan service/command belum ada.

## Perubahan dan manfaat

Service `PrivateFileCleanup` sekarang menjadi satu jalur penghapusan bukti pembayaran, Keuangan, sewa, serta upload publik/pendaftaran yang perlu dibersihkan. Sukses menghapus record antrean lama. False/exception membuat atau memperbarui satu job unik per path dengan konteks, jumlah percobaan, error terakhir, dan waktu percobaan. Percobaan berulang tidak menggandakan antrean.

Command `php artisan private-files:cleanup --dry-run` menampilkan antrean tanpa mutasi. `php artisan private-files:cleanup --limit=100` mencoba ulang maksimal 100 job (batas server 1–1000); sukses menghapus file dan record, gagal menambah attempts. Scheduler mendaftarkan retry tiap jam dengan `withoutOverlapping`. Deployment wajib menjalankan Laravel scheduler.

Delete payment pending tetap commit dan invoice ikut terhapus walau storage gagal, lalu bukti masuk antrean; transaksi database tidak diputar balik setelah commit. Cleanup rollback upload baru juga memakai antrean bila delete gagal. Jika database antrean sendiri tidak dapat ditulis, exception dilaporkan ke logger tanpa menutupi error transaksi asal; ini masih memerlukan monitoring log/infrastruktur.

Kriteria penerimaan: delete sukses tanpa antrean; false/exception tercatat; path dideduplikasi dan attempts naik; dry-run read-only; retry sukses menghapus file/job; controller after-commit mencatat kegagalan; migrasi dan scheduler valid.

## Validasi

| Kategori | Status | Bukti dan batasan |
|---|---|---|
| Functional | LULUS | 5 tes/26 asersi khusus mencakup sukses, gagal dua kali/deduplikasi, dry-run, retry command, dan delete payment after-commit. Command nyata pada SQLite/filesystem sintetis lulus. |
| User flow & transaction | LULUS lokal | Payment pending + invoice dihapus dalam transaksi; storage false setelah commit menghasilkan queue. Cleanup command menghapus file/job. Concurrency worker multi-node/database deployment belum diuji. |
| Responsive | BELUM DIUJI | Fitur ini command/background tanpa UI baru; responsive modul aplikasi tetap belum diuji browser. |
| Bug & regression | LULUS | Suite final sementara 143 tes/1027 asersi; controller pembayaran, Keuangan, sewa dan link publik tetap tercakup. |
| Retest | LULUS | Reproduksi delete gagal kini menghasilkan satu queue; retry nyata membuat antrean nol dan file hilang; suite penuh diulang. |
| User testing | BELUM DIUJI | Simulasi operator command lulus; belum ada UAT operator/infrastruktur nyata. |
| Security | LULUS dalam lingkup uji | Path berasal dari record/upload internal, command hanya console, tidak ada endpoint publik baru, detail error dibatasi, dependency/secret checks dijalankan. Permission host/storage produksi belum diuji. |

Migrasi SQLite baru lulus maju → rollback migrasi terakhir → maju. `schedule:list` menampilkan retry per jam. Data/file seluruhnya sintetis; tidak menyentuh produksi.

## Kelanjutan

Status tetap **belum siap operasional penuh** sampai responsive browser, UAT manusia, dan concurrency/database/storage deployment terverifikasi. Prioritas selanjutnya: metrik/alert bila antrean menumpuk atau DB queue gagal ditulis; partial payment/refund/remaining balance setelah definisi bisnis; rehearsal backup/migration/timezone staging. Jangan merge draft PR #4 sebelum gap wajib dan CI head terbaru selesai.

Skenario UAT/staging: buat file sintetis dan job; jalankan dry-run; putuskan akses delete storage; jalankan retry dan verifikasi attempts/error; pulihkan izin; retry dan verifikasi file/job hilang; pastikan scheduler memproses tanpa overlap serta log/alert operator tersedia. Jangan memakai bukti penghuni nyata untuk rehearsal.
