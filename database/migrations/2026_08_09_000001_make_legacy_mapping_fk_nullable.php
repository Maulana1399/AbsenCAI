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
            $table->foreignId('participation_id')->nullable()->change();
            $table->foreignId('event_id')->nullable()->change();
            $table->foreign('participation_id')->references('id')->on('participations')->nullOnDelete();
            $table->foreign('event_id')->references('id')->on('events')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('legacy_peserta_mappings', function (Blueprint $table) {
            $table->dropForeign(['participation_id']);
            $table->dropForeign(['event_id']);
            $table->foreignId('participation_id')->nullable(false)->change();
            $table->foreignId('event_id')->nullable(false)->change();
            $table->foreign('participation_id')->references('id')->on('participations')->restrictOnDelete();
            $table->foreign('event_id')->references('id')->on('events')->restrictOnDelete();
        });
    }
};
