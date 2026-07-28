<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_brackets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_class_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedInteger('participant_count');
            $table->string('status', 20)->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_brackets');
    }
};
