# Checkpoint tautan pemasukan manual historis — 9 Oktober 2026

## Source dan kebutuhan operasional

Prioritas ini dimulai setelah deduplikasi tagihan manual dipublikasikan pada draft PR #4 head `75adb0ba14973dd496724eaa6cdb28b4c4be259a`, tree teruji `3c7aa00034df996e7575707c8db6bc85c0250605`; CI run `37957485377` sukses. Main yang diperiksa tetap `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`.

Laporan rekonsiliasi sebelumnya hanya menunjukkan pembayaran lunas tanpa ledger dan memperingatkan operator agar tidak menggandakan pemasukan. Tidak ada cara aman untuk menandai bahwa pemasukan manual historis memang merupakan sumber pembayaran tersebut. Tes baseline fitur baru mendapat kandidat kosong dan endpoint link 404.

## Perubahan dan manfaat

Rekonsiliasi kini menawarkan pemasukan manual hanya bila **tanggal, nominal dua desimal, jenis pemasukan, dan kategori Sewa Kamar persis sama**. Sistem tidak memilih otomatis. Operator harus memiliki hak Ubah Keuangan dan Ubah Data Sewa, memilih kandidat, memeriksa deskripsi/bukti, serta memberi alasan 10–500 karakter.

Link dijalankan dengan lock payment lalu ledger di satu transaksi. Payment harus lunas/belum memiliki ledger; entry harus belum tertaut dan bukan pembalikan. Constraint unik `keuangans.payment_id` tetap menjadi pertahanan database. Setelah ditautkan, pemasukan menjadi immutable dan sumbernya dibedakan sebagai **rekonsiliasi manual** pada daftar, detail, dan CSV.

Setiap link/unlink menyimpan payment, ledger, operator, waktu, aksi, dan alasan pada tabel audit. Riwayat ditampilkan di detail Pembayaran dan Keuangan dengan output di-escape. Operator dapat melepas hanya link manual dengan alasan; payment dan pemasukan tidak dihapus. Ledger hasil approval otomatis tidak dapat dilepas. Kegagalan insert audit membatalkan perubahan link.

Kriteria penerimaan: kandidat mismatch tidak ditampilkan/ditolak; alasan pendek ditolak; reuse payment/ledger ditolak; izin ganda wajib; automatic ledger immutable; link/unlink tidak menghapus nominal/histori; audit tampil; CSV membedakan sumber; kegagalan audit rollback total.

## Validasi

| Kategori | Status | Bukti dan batasan |
|---|---|---|
| Functional | LULUS | Kandidat exact/mismatch, alasan batas, link, retry, unlink, automatic ledger, audit UI dan CSV diuji. |
| User flow & transaction | LULUS lokal | HTTP nyata: login → laporan → CSRF reject → kandidat → link → detail → retry reject → unlink; payment/entry tetap ada. Trigger kegagalan audit membuktikan rollback. Concurrency DB deployment belum diuji. |
| Responsive | BELUM DIUJI | Form memakai table-responsive/flex Bootstrap dan build lulus, tetapi tidak ada pemeriksaan browser pada ponsel/tablet/desktop. |
| Bug & regression | LULUS | Regresi label role Keuangan-only ditemukan oleh suite, diperbaiki, lalu tes terdampak dan suite diulang. Suite final sementara 138 tes/1001 asersi; frontend 4/4. |
| Retest | LULUS | 18 tes payment-ledger/reconciliation lulus setelah perbaikan label; suite penuh, route/view compile, build, migrasi rollback/remigrate diulang. |
| User testing | BELUM DIUJI | Simulasi agen admin/operator via HTTP lulus; UAT manusia belum dilakukan. |
| Security | LULUS dalam lingkup uji | Auth, CSRF 419, dua izin update, validasi ID/reason, strict server-side rematch, row lock/unique, XSS escaping, immutable auto-ledger, audit dependency dan secret scan. Bukan pentest penuh. |

Migrasi SQLite baru lulus maju → rollback migrasi terakhir → maju. Composer dan npm audit nol advisory. Seluruh data sintetis; tidak ada pesan penghuni, perubahan produksi, atau backfill otomatis.

## Kelanjutan

Status tetap **belum siap operasional penuh**: responsive browser, UAT manusia, dan concurrency pada jenis database deployment belum diverifikasi. Backlog berikutnya: durable cleanup/pemantauan penghapusan berkas pascacommit; saldo cicilan/partial payment/refund hanya setelah definisi bisnis; rehearsal backup, migrasi, dan timezone pada staging representatif. Draft PR #4 tetap draft sampai gap wajib selesai.

Skenario UAT: siapkan payment lunas historis dan dua ledger—satu exact, satu mismatch; operator membuka Rekonsiliasi, memeriksa bukti, menautkan exact dengan alasan; cek daftar/detail/CSV dan hilangnya temuan; coba edit/delete; lepas dengan alasan; pastikan temuan kembali dan histori dua aksi tetap terlihat. Ulangi dengan role view-only dan pada tiga viewport.
