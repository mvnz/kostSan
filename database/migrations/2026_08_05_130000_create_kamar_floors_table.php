<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kamar_floors', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('number')->unique();
            $table->string('name', 100);
            $table->timestamps();
        });

        DB::table('kamar_floors')->insert([
            [
                'number' => 1,
                'name' => 'Lantai 1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'number' => 2,
                'name' => 'Lantai 2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kamar_floors');
    }
};
