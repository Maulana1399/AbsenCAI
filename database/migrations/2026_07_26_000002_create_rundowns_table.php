<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rundowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->string('name');
            $table->date('rundown_date')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->unique(['event_id', 'name']);
            $table->index('event_id');
            $table->index('rundown_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rundowns');
    }
};
