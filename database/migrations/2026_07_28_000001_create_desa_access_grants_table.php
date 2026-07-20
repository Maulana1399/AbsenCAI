<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desa_access_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->foreignId('desa_id')->constrained('desas')->restrictOnDelete();
            $table->string('token_hash', 64);
            $table->string('token_prefix', 16);
            $table->dateTime('valid_from');
            $table->dateTime('valid_until');
            $table->dateTime('revoked_at')->nullable();
            $table->string('nonce', 64)->unique();
            $table->dateTime('nonce_expires_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('token_prefix');
            $table->index('nonce');
            $table->index(['event_id', 'desa_id']);
            $table->index('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desa_access_grants');
    }
};
