<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_classes', function (Blueprint $table) {
            $table->foreignId('competition_category_id')->after('event_id')
                ->constrained('competition_categories')->restrictOnDelete();
            $table->index('competition_category_id');
        });
    }

    public function down(): void
    {
        Schema::table('competition_classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('competition_category_id');
        });
    }
};
