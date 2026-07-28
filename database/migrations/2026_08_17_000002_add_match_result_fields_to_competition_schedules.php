<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_schedules', function (Blueprint $table) {
            $table->foreignId('winner_registration_id')
                ->nullable()
                ->after('required_participants')
                ->constrained('competition_registrations')
                ->nullOnDelete();

            $table->string('finish_reason', 50)->nullable()->after('winner_registration_id');
            $table->text('finish_notes')->nullable()->after('finish_reason');

            $table->dateTime('finished_at')->nullable()->after('finish_notes');

            $table->foreignId('finished_by')
                ->nullable()
                ->after('finished_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('competition_schedules', function (Blueprint $table) {
            $table->dropForeign(['winner_registration_id']);
            $table->dropForeign(['finished_by']);
            $table->dropColumn([
                'winner_registration_id',
                'finish_reason',
                'finish_notes',
                'finished_at',
                'finished_by',
            ]);
        });
    }
};
