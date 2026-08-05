<?php

namespace App\Console\Commands;

use App\Models\Absensi;
use App\Models\EventAttendance;
use App\Models\IzinAbsensi;
use Illuminate\Console\Command;

class AttendanceStatus extends Command
{
    protected $signature = 'attendance:status';

    protected $description = 'Show current attendance system status (read-only)';

    public function handle(): int
    {
        $legacyWriteEnabled = config('features.attendance_legacy_write', true);

        $canonicalCount = EventAttendance::count();
        $caiCanonicalCount = EventAttendance::whereNotNull('sesi_absensi_id')->count();
        $pengajianCanonicalCount = EventAttendance::whereNull('sesi_absensi_id')->count();

        $legacyAbsensiCount = Absensi::count();
        $legacyIzinCount = IzinAbsensi::count();

        $unmappable = Absensi::whereDoesntHave('sesi.event')
            ->orWhere(function ($q) {
                $q->whereHas('sesi.event', function ($eq) {
                    $eq->whereNotNull('id');
                })->whereRaw('1 = 0');
            })
            ->count();

        $this->line('=== Attendance System Status ===');
        $this->line('');

        $mode = $legacyWriteEnabled
            ? 'DUAL-WRITE (canonical + legacy)'
            : 'CANONICAL-WRITE + LEGACY-FALLBACK';

        $this->line('Legacy write config: '.($legacyWriteEnabled ? 'ENABLED' : 'DISABLED'));
        $this->line("Operating mode:     {$mode}");
        $this->line('');

        $this->line('--- EventAttendance (canonical) ---');
        $this->line("  Total:     {$canonicalCount}");
        $this->line("  CAI:       {$caiCanonicalCount} (sesi_absensi_id IS NOT NULL)");
        $this->line("  Pengajian: {$pengajianCanonicalCount} (sesi_absensi_id IS NULL)");
        $this->line('');

        $this->line('--- Legacy Tables ---');
        $this->line("  Absensi (hadir):    {$legacyAbsensiCount}");
        $this->line("  IzinAbsensi (izin): {$legacyIzinCount}");
        $this->line('');

        $this->line('--- Fallback ---');
        $this->line('  Legacy fallback reads: ACTIVE (AttendanceReadService)');
        $this->line('  Historical data:       PRESERVED (no legacy tables deleted)');
        $this->line('');

        $this->warn('This command is READ-ONLY. No data was modified.');

        return 0;
    }
}
