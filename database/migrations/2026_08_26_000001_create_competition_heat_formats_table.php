<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Heat Format: konfigurasi per-round untuk kelas heat (individual_heat /
        // team_heat) — peserta per heat + jumlah lolos (top-N) per heat.
        //
        // Root cause mengapa dibutuhkan:
        // - `competition_schedules.required_participants` hanya menyimpan kapasitas
        //   SATU heat yang sudah dibuat, dan `sort_order = round*100 + heatIndex`
        //   hanya menyandikan round. Tidak ada tempat persisten untuk "jumlah
        //   peserta per heat" dan "jumlah lolos per heat" PADA TINGKAT ROUND.
        // - Sprint R4H sengaja tidak menyimpan `top_n` (parameter method). Heat
        //   Manager sekarang harus menyimpan format agar bisa auto-generate heat
        //   dan auto-generate round berikutnya tanpa hardcode.
        // - Test H (single-round): next-round hanya dibuat bila format round
        //   berikutnya sudah didefinisikan — kebutuhan state persisten yang sama.
        //
        // Satu baris per (class, round); round 1 = babak penyisihan, round N
        // berikutnya di-generate dari qualifier babak sebelumnya.
        Schema::create('competition_heat_formats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_class_id')->constrained('competition_classes')->cascadeOnDelete();
            $table->unsignedInteger('round')->default(1);
            $table->unsignedInteger('participants_per_heat')->default(1);
            $table->unsignedInteger('qualifiers_per_heat')->default(1);
            $table->timestamps();

            $table->unique(['competition_class_id', 'round'], 'uniq_heat_format_class_round');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_heat_formats');
    }
};
