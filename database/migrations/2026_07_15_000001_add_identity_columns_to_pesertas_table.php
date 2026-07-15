<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesertas', function (Blueprint $table) {
            $table->string('participant_number')->nullable()->unique()->after('nip');
            $table->string('attendance_code')->nullable()->unique()->after('participant_number');
        });
    }

    public function down(): void
    {
        Schema::table('pesertas', function (Blueprint $table) {
            $table->dropUnique(['participant_number']);
            $table->dropUnique(['attendance_code']);
            $table->dropColumn(['participant_number', 'attendance_code']);
        });
    }
};
