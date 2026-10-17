# Penghapusan sewa dan penyelesaian kamar

## Kebutuhan dan penerimaan sebelum perubahan

17 Oktober 2026: main terbaru `3b23279a6e8cd0c3a1b655e1e472fe004a59b596`; basis kerja PR #9 `d105d176dd96745b03195fac7a1c4460d9c2d6eb`, CI39034945350 sukses. PR #4–9 masih draft tanpa perubahan baru; worktree bersih, AGENTS.md tidak ditemukan. Prioritas baru terpisah dari pembalikan pembayaran.

Source menunjukkan destroy mengunci sewa lalu memperbarui kamar; selesaiSewa mengunci kamar lalu sewa. Dugaan: hapus sewa tanpa histori bersamaan dengan checkout dapat deadlock. Belum diklaim bug runtime sebelum reproduksi MySQL. Manfaat: operator dapat menyelesaikan aksi yang diserialkan tanpa error database; request penghapusan yang sudah terikat ke kamar lama tidak boleh mengubah kamar baru tanpa lock.

Cakupan: penghapusan sewa saja, bukan rewrite update/perpanjangan, bukan aturan bisnis baru tentang histori. Penerimaan: reproduksi menggunakan dua koneksi/controller asli pada schema CI sintetis, parent menahan lock kamar dan worker hapus teramati menunggu kamar, checkout parent berhasil lalu delete menyelesaikan tanpa deadlock; kamar tersedia, sewa tanpa histori hilang. Request lama setelah pindah kamar via PUT nyata harus ditolak dengan instruksi muat ulang, tanpa mutasi/bukti terhapus; request baru berhasil. Riwayat pembayaran/reservasi dan private-file cleanup tetap terlindungi. SQLite hanya menguji perilaku, tidak membuktikan row lock MySQL. Dokumentasikan hasil merah sebelum fix dan hijau sesudahnya, tujuh kategori final, self-review serta SHA/CI di sini/PR.
