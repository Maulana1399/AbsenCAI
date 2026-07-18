<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_registrations', function (Blueprint $table) {
            $table->foreignId('category_definition_id')->nullable()->after('activity_id')->constrained('category_definitions')->nullOnDelete();
            $table->index('category_definition_id');
        });
    }

    public function down(): void
    {
        Schema::table('activity_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_definition_id');
        });
    }
};
