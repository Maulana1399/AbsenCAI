<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_registration_id')->constrained('competition_registrations')->cascadeOnDelete()->unique();
            $table->unsignedInteger('position')->nullable();
            $table->string('status', 50)->nullable();
            $table->decimal('score', 10, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('competition_registration_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_outcomes');
    }
};
