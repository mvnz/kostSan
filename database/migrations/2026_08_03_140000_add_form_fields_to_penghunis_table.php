<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('penghunis', function (Blueprint $table) {
            $table->string('nomor_kamar', 30)->nullable()->after('nama');
            $table->unsignedTinyInteger('jumlah_kunci')->nullable()->after('nomor_kamar');
            $table->string('tempat_lahir', 100)->nullable()->after('jumlah_kunci');
            $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
            $table->text('alamat_ktp')->nullable()->after('email');
            $table->date('tanggal_mulai_tinggal')->nullable()->after('alamat');
            $table->unsignedSmallInteger('lama_sewa_bulan')->nullable()->after('tanggal_mulai_tinggal');

            $table->string('kontak_darurat_nama', 150)->nullable()->after('lama_sewa_bulan');
            $table->string('kontak_darurat_hubungan', 100)->nullable()->after('kontak_darurat_nama');
            $table->string('kontak_darurat_telepon', 50)->nullable()->after('kontak_darurat_hubungan');

            $table->enum('jenis_kendaraan', ['tidak_ada', 'motor'])->nullable()->after('kontak_darurat_telepon');
            $table->string('kendaraan_merek_tipe', 100)->nullable()->after('jenis_kendaraan');
            $table->string('kendaraan_warna', 50)->nullable()->after('kendaraan_merek_tipe');
            $table->string('kendaraan_nomor_polisi', 30)->nullable()->after('kendaraan_warna');

            $table->decimal('harga_sewa', 12, 2)->nullable()->after('kendaraan_nomor_polisi');
            $table->date('tanggal_jatuh_tempo')->nullable()->after('harga_sewa');

            $table->boolean('setuju_peraturan')->default(false)->after('tanggal_jatuh_tempo');
            $table->text('catatan_pengelola')->nullable()->after('setuju_peraturan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penghunis', function (Blueprint $table) {
            $table->dropColumn([
                'nomor_kamar',
                'jumlah_kunci',
                'tempat_lahir',
                'tanggal_lahir',
                'alamat_ktp',
                'tanggal_mulai_tinggal',
                'lama_sewa_bulan',
                'kontak_darurat_nama',
                'kontak_darurat_hubungan',
                'kontak_darurat_telepon',
                'jenis_kendaraan',
                'kendaraan_merek_tipe',
                'kendaraan_warna',
                'kendaraan_nomor_polisi',
                'harga_sewa',
                'tanggal_jatuh_tempo',
                'setuju_peraturan',
                'catatan_pengelola',
            ]);
        });
    }
};
