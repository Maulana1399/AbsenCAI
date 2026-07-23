<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legacy_peserta_mappings', function (Blueprint $table) {
            $table->dropForeign(['participation_id']);
            $table->dropForeign(['event_id']);
            $table->dropUnique('legacy_peserta_mappings_participation_id_unique');
        });

        Schema::table('legacy_peserta_mappings', function (Blueprint $table) {
            $table->dropColumn(['participation_id', 'event_id', 'backfill_batch_id']);
        });
    }

    public function down(): void
    {
        Schema::table('legacy_peserta_mappings', function (Blueprint $table) {
            $table->foreignId('participation_id')->nullable()->constrained('participations')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('backfill_batch_id')->nullable();
            $table->unique('participation_id', 'legacy_peserta_mappings_participation_id_unique');
        });
    }
};
