# Checkpoint: hunian dan tagihan sewa menunggak

Tanggal uji: 2026-10-10 (Asia/Jakarta)

## Temuan terkonfirmasi

Pendaftaran publik membuat sewa berstatus `menunggak` sampai pembayaran disetujui. Namun, laporan hunian, penghuni aktif pada dashboard, dan bulk billing hanya membaca status `aktif`. Akibatnya sewa menunggak yang tetap berjalan dapat hilang dari statistik dan daftar pembuatan tagihan, meskipun `RoomAvailability` masih menganggap kamar ditempati.

## Perbaikan dan peningkatan operasional

- Definisi hunian berjalan memakai status `aktif` atau `menunggak` dengan batas tanggal masuk/checkout.
- Laporan bulanan menghitung kamar menunggak satu kali, termasuk mode riwayat.
- Status kamar saat ini dan dashboard mengabaikan sewa masa depan, tetapi menampilkan penghuni menunggak yang sudah masuk.
- Bulk billing menyertakan sewa menunggak yang periodenya beririsan dan tetap memakai perlindungan cakupan/duplikasi yang ada.
- Preview bulk menampilkan badge status sewa agar operator memahami alasan penghuni menunggak tetap ditagih.
- Denah kamar memakai metadata sewa menunggak untuk aksi penyelesaian dan informasi periode.

## Kriteria penerimaan

1. Sewa menunggak berjalan muncul pada preview bulk dan dapat dibuatkan satu tagihan: lulus.
2. Retry/duplikasi dan transaksi batch tetap dilindungi oleh tes regresi yang ada: lulus.
3. Sewa menunggak berjalan dihitung dalam laporan hunian; sewa masa depan tidak dianggap penghuni saat ini: lulus.
4. Dashboard menghitung penghuni menunggak yang sudah masuk, tidak menghitung sewa masa depan: lulus.

Targeted suite bulk billing, occupancy, dan integritas operasional: 35 tes, 211 assertions, lulus.

## Batasan

Validasi ini memakai SQLite dan data sintetis. Concurrency pada engine database deployment, responsive browser, dan UAT operator nyata masih perlu dilakukan sebelum merge/produksi.
