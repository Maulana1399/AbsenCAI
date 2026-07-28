<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('competition_schedules')
            ->where('status', 'NowPlaying')
            ->update(['status' => 'Playing']);

        Schema::table('competition_schedules', function (Blueprint $table) {
            $table->unsignedInteger('required_participants')->default(1)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('competition_schedules', function (Blueprint $table) {
            $table->dropColumn('required_participants');
        });

        DB::table('competition_schedules')
            ->where('status', 'Playing')
            ->update(['status' => 'NowPlaying']);
    }
};
