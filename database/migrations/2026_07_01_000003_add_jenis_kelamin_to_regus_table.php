<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regus', function (Blueprint $table) {
            $table->enum('jenis_kelamin', ['Laki - Laki', 'Perempuan'])->nullable()->after('regu');
        });
    }

    public function down(): void
    {
        Schema::table('regus', function (Blueprint $table) {
            $table->dropColumn('jenis_kelamin');
        });
    }
};
