<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Opsi bracket "Perebutan Juara 3" (Bronze Match). Backward compatible:
        // default false = existing behavior (semifinal losers tied 3rd).
        Schema::table('competition_brackets', function (Blueprint $table) {
            $table->boolean('third_place_match')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('competition_brackets', function (Blueprint $table) {
            $table->dropColumn('third_place_match');
        });
    }
};
