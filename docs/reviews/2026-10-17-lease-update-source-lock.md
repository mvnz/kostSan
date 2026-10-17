# Update sewa setelah perpindahan kamar

## Kebutuhan dan kriteria sebelum menulis kode aplikasi

17 Oktober 2026 22.05 WIB: fetch main/default main tetap `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`; head PR #10 `5a27a639736e3cdbdb538ba4237706d0bb6ad461`, CI39039370608 sukses. PR #4–10 draft, tanpa issue/komentar baru; worktree bersih, AGENTS.md tidak ditemukan. Checkpoint PR #10 menunjuk race update sebagai dugaan belum direproduksi, bukan bug yang sudah diperbaiki. Tidak mengulang perbaikan destroy.

Source update mengunci kamar binding dan kamar tujuan, lalu me-refresh sewa; kamar aktual setelah perpindahan lain bisa berada di luar kedua lock, tetapi tetap di-sync sesudah write. Dugaan: urutan sewa→kamar aktual dapat deadlock dengan checkout kamar→sewa. Manfaat: update yang tidak memegang semua lock sumber ditolak sebelum mutasi, dengan pemulihan jelas, tanpa melarang perpindahan sah.

Penerimaan: bind A pada worker, pause via pipe; controller parent benar-benar memindahkan A→B, lalu menahan lock B; lanjutkan request lama A→C. Source awal harus direproduksi gagal (deadlock atau perubahan tanpa proteksi), bukan hanya diperiksa string. Setelah fix worker harus menolak sebelum menyentuh B, lease/proof/nominal tidak berubah, checkout B dapat selesai tanpa deadlock. SQLite menguji penolakan, no-file-write dan fresh retry; kasus kamar aktual sudah sama dengan tujuan tetap diizinkan karena kamar itu sudah terkunci. Jangan mengubah aturan bisnis/histori/nominal, permission, migrasi, dependency atau lock order. MySQL CI sintetis terisolasi membuktikan row locks; SQLite/storage fake bukan bukti concurrency/storage deployment. PR terpisah dari #10; checkpoint final memuat self-review, tujuh kategori dan batas staging/UAT.
