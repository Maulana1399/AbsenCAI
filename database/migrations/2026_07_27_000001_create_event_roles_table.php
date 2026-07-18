<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('scope')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'name']);
            $table->unique(['event_id', 'code']);
            $table->index('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_roles');
    }
};
