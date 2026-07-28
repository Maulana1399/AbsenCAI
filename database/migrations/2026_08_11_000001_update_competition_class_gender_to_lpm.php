<?php

use App\Models\CompetitionClass;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        CompetitionClass::whereNull('gender')->orWhere('gender', '')->update(['gender' => 'M']);

        Schema::table('competition_classes', function (Blueprint $table) {
            $table->string('gender', 1)->nullable(false)->default(null)->change();
        });

        Schema::table('competition_classes', function (Blueprint $table) {
            $table->string('gender', 1)->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('competition_classes', function (Blueprint $table) {
            $table->string('gender', 1)->nullable()->default(null)->change();
        });
    }
};
