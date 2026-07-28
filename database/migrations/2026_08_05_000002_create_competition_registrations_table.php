<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participation_id')->constrained('participations')->restrictOnDelete();
            $table->foreignId('competition_category_id')->constrained('competition_categories')->restrictOnDelete();
            $table->foreignId('competition_class_id')->constrained('competition_classes')->restrictOnDelete();
            $table->string('registration_type', 20)->default('individual');
            $table->timestamps();

            $table->unique(['participation_id', 'competition_class_id'], 'uniq_participation_class');
            $table->index('competition_category_id');
            $table->index('competition_class_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_registrations');
    }
};
