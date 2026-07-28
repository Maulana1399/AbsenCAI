<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_match_officials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 50); // referee, judge, scorer, supervisor
            $table->timestamps();

            $table->unique(['competition_schedule_id', 'user_id', 'role'], 'uniq_schedule_user_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_match_officials');
    }
};
