<?php

use App\Models\CompetitionClass;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (CompetitionClass::whereNull('gender')->cursor() as $class) {
            $lower = mb_strtolower($class->name);
            $gender = 'L';
            if (str_contains($lower, 'putri') || str_contains($lower, 'perempuan')) {
                $gender = 'P';
            }
            $class->update(['gender' => $gender]);
        }

        Schema::table('competition_classes', function (Blueprint $table) {
            $table->string('gender', 1)->nullable(false)->default('L')->change();
        });
    }

    public function down(): void
    {
        Schema::table('competition_classes', function (Blueprint $table) {
            $table->string('gender', 1)->nullable()->default(null)->change();
        });
    }
};
