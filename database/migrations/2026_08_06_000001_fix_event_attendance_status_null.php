<?php

use App\Models\EventAttendance;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        EventAttendance::whereNull('status')->update(['status' => EventAttendance::STATUS_HADIR]);
    }

    public function down(): void {}
};
