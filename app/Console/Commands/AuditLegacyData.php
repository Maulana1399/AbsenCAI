<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditLegacyData extends Command
{
    protected $signature = 'audit:legacy-data';

    protected $description = 'Read-only audit of legacy peserta data quality for S3.5 backfill planning';

    public function handle(): int
    {
        $this->section('LEGACY PESERTA SUMMARY');

        $this->line($this->keyValue('total peserta', peserta::count()));

        $blankNama = peserta::where(function ($q) {
            $q->whereNull('nama')->orWhere(DB::raw('TRIM(nama)'), '');
        })->count();
        $this->line($this->keyValue('blank/null nama', $blankNama));

        $nullNip = peserta::whereNull('nip')->count();
        $this->line($this->keyValue('null NIP', $nullNip));

        $dupNip = peserta::select('nip', DB::raw('COUNT(*) as cnt'))
            ->groupBy('nip')
            ->having('cnt', '>', 1)
            ->count();
        $this->line($this->keyValue('duplicate NIP', $dupNip));

        $minNip = peserta::min('nip');
        $maxNip = peserta::max('nip');
        $this->line($this->keyValue('minimum NIP', $minNip ?? 'N/A'));
        $this->line($this->keyValue('maximum NIP', $maxNip ?? 'N/A'));

        $rangeM = peserta::whereBetween('nip', [1000, 1999])->count();
        $rangeF = peserta::whereBetween('nip', [2000, 2999])->count();
        $rangeOther = peserta::where(function ($q) {
            $q->where('nip', '<', 1000)->orWhere('nip', '>', 2999);
        })->count();
        $this->line('  NIP distribution:');
        $this->line('    1000–1999: ' . $rangeM);
        $this->line('    2000–2999: ' . $rangeF);
        $this->line('    outside expected range: ' . $rangeOther);

        $this->section('IDENTIFIER QUALITY');

        $nullParticipantNumber = peserta::whereNull('participant_number')->count();
        $this->line($this->keyValue('null participant_number', $nullParticipantNumber));

        $dupParticipantNumber = peserta::whereNotNull('participant_number')
            ->select('participant_number', DB::raw('COUNT(*) as cnt'))
            ->groupBy('participant_number')
            ->having('cnt', '>', 1)
            ->count();
        $this->line($this->keyValue('duplicate participant_number', $dupParticipantNumber));

        $nullAttendanceCode = peserta::whereNull('attendance_code')->count();
        $this->line($this->keyValue('null attendance_code', $nullAttendanceCode));

        $dupAttendanceCode = peserta::whereNotNull('attendance_code')
            ->select('attendance_code', DB::raw('COUNT(*) as cnt'))
            ->groupBy('attendance_code')
            ->having('cnt', '>', 1)
            ->count();
        $this->line($this->keyValue('duplicate attendance_code', $dupAttendanceCode));

        $this->section('GENDER QUALITY');

        $genders = peserta::select('jenis_kelamin', DB::raw('COUNT(*) as cnt'))
            ->groupBy('jenis_kelamin')
            ->orderByDesc('cnt')
            ->get();

        $this->line('  Distinct jenis_kelamin values:');
        foreach ($genders as $g) {
            $canonical = $this->normalizeGender($g->jenis_kelamin);
            $label = $g->jenis_kelamin === null ? 'NULL' : "'{$g->jenis_kelamin}'";
            $this->line("    {$label}: {$g->cnt} → {$canonical}");
        }

        $this->section('NAME / IDENTITY QUALITY');

        $dupNames = DB::table('pesertas')
            ->selectRaw('LOWER(TRIM(nama)) as normalized_name, COUNT(*) as cnt')
            ->groupByRaw('LOWER(TRIM(nama))')
            ->havingRaw('COUNT(*) > 1')
            ->orderByRaw('COUNT(*) DESC')
            ->get();

        $this->line('  Duplicate normalized names:');
        $this->line('    Total groups: ' . $dupNames->count());
        $dupNames->take(20)->each(function ($row) {
            $this->line("    {$row->normalized_name} | count={$row->cnt}");
        });
        if ($dupNames->count() > 20) {
            $this->line('    ... and ' . ($dupNames->count() - 20) . ' more groups');
        }

        $dupNameDesa = DB::table('pesertas')
            ->whereNotNull('desa_id')
            ->selectRaw('LOWER(TRIM(nama)) as normalized_name, desa_id, COUNT(*) as cnt')
            ->groupByRaw('LOWER(TRIM(nama))')
            ->groupBy('desa_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderByRaw('COUNT(*) DESC')
            ->get();

        $this->line('  Duplicate normalized name + desa_id:');
        $this->line('    Total groups: ' . $dupNameDesa->count());
        $dupNameDesa->take(20)->each(function ($row) {
            $this->line("    {$row->normalized_name} | desa_id={$row->desa_id} | count={$row->cnt}");
        });
        if ($dupNameDesa->count() > 20) {
            $this->line('    ... and ' . ($dupNameDesa->count() - 20) . ' more groups');
        }

        $conflictingGenders = DB::table('pesertas')
            ->whereNotNull('desa_id')
            ->whereNotNull('jenis_kelamin')
            ->selectRaw("LOWER(TRIM(nama)) as normalized_name, desa_id, GROUP_CONCAT(DISTINCT jenis_kelamin) as genders, COUNT(*) as cnt")
            ->groupByRaw('LOWER(TRIM(nama))')
            ->groupBy('desa_id')
            ->havingRaw('COUNT(DISTINCT jenis_kelamin) > 1')
            ->orderByRaw('COUNT(*) DESC')
            ->get();

        $this->line('  Conflicting gender for same normalized name + desa_id:');
        $this->line('    Total groups: ' . $conflictingGenders->count());
        $conflictingGenders->take(20)->each(function ($row) {
            $this->line("    {$row->normalized_name} | desa_id={$row->desa_id} | genders={$row->genders} | count={$row->cnt}");
        });
        if ($conflictingGenders->count() > 20) {
            $this->line('    ... and ' . ($conflictingGenders->count() - 20) . ' more groups');
        }

        $this->section('NEW DOMAIN STATE');

        $this->line($this->keyValue('total people', Person::count()));
        $this->line($this->keyValue('total participations', Participation::count()));
        $this->line($this->keyValue('total events', Event::count()));

        $this->section('LEGACY EVENT STATE');

        $legacyEvent = Event::where('slug', 'cai-operational')->first();

        if ($legacyEvent) {
            $this->line('  exists: yes');
            $this->line($this->keyValue('  id', $legacyEvent->id));
            $this->line($this->keyValue('  name', $legacyEvent->name));
            $this->line($this->keyValue('  slug', $legacyEvent->slug));
            $this->line($this->keyValue('  status', $legacyEvent->status));
        } else {
            $this->line('  exists: no');
            $this->line("  (Event with slug 'cai-operational' not found. Run LegacyEventSeeder.)");
        }

        $this->line('');
        $this->info('Database writes performed: 0');

        return Command::SUCCESS;
    }

    private function section(string $title): void
    {
        $this->line('');
        $this->line(str_repeat('=', 60));
        $this->line($title);
        $this->line(str_repeat('-', 60));
    }

    private function keyValue(string $key, mixed $value): string
    {
        return "  {$key}: {$value}";
    }

    private function normalizeGender(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return 'null';
        }

        $normalized = strtolower(str_replace([' ', '-'], '', trim($value)));

        return match ($normalized) {
            'l', 'lakilaki' => 'L',
            'p', 'perempuan' => 'P',
            default => 'UNKNOWN',
        };
    }
}
