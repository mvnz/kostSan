# Checkpoint: keamanan denah kamar dan izin operasi

Tanggal uji: 2026-10-10 (Asia/Jakarta)

## Temuan terkonfirmasi

1. Denah memasukkan nomor/tipe kamar ke handler JavaScript inline dengan `addslashes`, yang bukan encoding aman untuk konteks HTML+JavaScript.
2. Nama penghuni dari database dirangkai ke `innerHTML` saat riwayat kamar dibuka. Nilai seperti tag/script dapat menjadi DOM XSS pada sesi operator.
3. Semua tombol mutasi denah dikelompokkan di bawah satu izin UI, sedangkan endpoint memakai izin modul berbeda. Role terbatas dapat melihat kontrol yang berakhir 403, atau JavaScript gagal ketika sebagian elemen tidak dirender.
4. Endpoint selesai/perpanjang sewa salah dipetakan ke izin edit master kamar, bukan izin update operasi Sewa Kamar.
5. Nama lantai dan tipe kamar disisipkan ke JavaScript konfirmasi hapus menggunakan `addslashes`, yang tidak aman untuk gabungan konteks HTML dan JavaScript.

## Perbaikan

- Handler menerima elemen kamar (`openKamar(this)`) dan membaca nilai dari atribut `data-*` yang di-escape Blade.
- JSON histori memakai flag hex dan seluruh nilai dinamis dibuat melalui `createElement` + `textContent`; tidak ada lagi rangkaian data penghuni ke `innerHTML`.
- Tombol layout, buat sewa, link pendaftaran, dan operasi selesai/perpanjang masing-masing mengikuti izin endpoint yang relevan.
- JavaScript memeriksa keberadaan kontrol opsional, sehingga role read-only dapat membuka detail tanpa error.
- Tautan edit histori hanya dibuat ketika pengguna mempunyai izin update Data Sewa.
- Middleware selesai/perpanjang memakai `manajemen_sewa.sewa_kamar:update`.
- Konfirmasi hapus lantai/tipe membaca pesan dari atribut `data-confirm-message` yang di-escape HTML, tanpa menyisipkan nilai database ke source JavaScript.

## Bukti

- Payload sintetis pada nomor/tipe kamar dan nama penghuni tidak muncul sebagai tag/script mentah; markup memakai data ter-escape dan DOM text nodes.
- Role read-only tidak menerima kontrol mutasi.
- Role update Sewa Kamar dapat menyelesaikan sewa tanpa memerlukan izin master kamar.
- Targeted security/access regression: 13 tes, 103 assertions, lulus.
- Tambahan regresi output master data: 2 tes, 8 assertions, lulus untuk payload quote/tag pada lantai dan tipe kamar.

Pengujian ini memverifikasi output server dan logika DOM source. Eksekusi visual/browser nyata tetap belum tersedia dan tidak diklaim lulus.
