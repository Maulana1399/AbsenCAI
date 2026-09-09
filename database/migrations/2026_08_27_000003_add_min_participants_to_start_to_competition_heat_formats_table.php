<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Minimum peserta agar heat bisa "Ready/Start" — terpisah dari kapasitas.
        //
        // Pemisahan konsep:
        // - `required_participants` (schedule) / `participants_per_heat` (format)
        //   = KAPASITAS maksimum sebuah heat, BUKAN syarat untuk dimainkan.
        // - `min_participants_to_start` (format) = jumlah minimum peserta aktual
        //   agar heat bisa Ready → Start. Default 2 = backward compatible dengan
        //   seluruh perilaku existing (heat 1/2 tetap Scheduled, 2/2 Ready).
        Schema::table('competition_heat_formats', function (Blueprint $table) {
            $table->unsignedInteger('min_participants_to_start')->default(2)->after('participants_per_heat');
        });
    }

    public function down(): void
    {
        Schema::table('competition_heat_formats', function (Blueprint $table) {
            $table->dropColumn('min_participants_to_start');
        });
    }
};
