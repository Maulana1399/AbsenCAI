<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('identity_correction_requests', function (Blueprint $table) {
            $table->text('operator_notes')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('identity_correction_requests', function (Blueprint $table) {
            $table->dropColumn('operator_notes');
        });
    }
};
