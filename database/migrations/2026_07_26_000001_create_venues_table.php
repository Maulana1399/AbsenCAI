<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('location_detail')->nullable();
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'name']);
            $table->unique(['event_id', 'code']);
            $table->index('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
