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
        Schema::table('kamars', function (Blueprint $table) {
            $table->unsignedTinyInteger('layout_floor')->nullable()->after('status');
            $table->unsignedSmallInteger('layout_row')->nullable()->after('layout_floor');
            $table->unsignedSmallInteger('layout_col')->nullable()->after('layout_row');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kamars', function (Blueprint $table) {
            $table->dropColumn(['layout_floor', 'layout_row', 'layout_col']);
        });
    }
};
