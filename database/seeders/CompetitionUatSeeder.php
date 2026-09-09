<?php

namespace Database\Seeders;

use App\Models\CompetitionBracket;
use App\Models\CompetitionBracketMatch;
use App\Models\CompetitionCategory;
use App\Models\CompetitionClass;
use App\Models\CompetitionHeatFormat;
use App\Models\CompetitionHeatResult;
use App\Models\CompetitionRegistration;
use App\Models\CompetitionSchedule;
use App\Models\CompetitionScheduleEntry;
use App\Models\CompetitionTeam;
use App\Models\desa;
use App\Models\Event;
use App\Models\kelompok;
use App\Models\Person;
use App\Services\Competition\CompetitionBracketSeederService;
use App\Services\Competition\CompetitionHeatManagerService;
use App\Services\Competition\CompetitionRegistrationService;
use App\Services\Competition\CompetitionTeamFormationService;
use App\Support\CompetitionFormat;
use App\Support\CompetitionResultType;
use App\Support\CompetitionStatus;
use Illuminate\Database\Seeder;
use Illuminate\Validation\ValidationException;

/**
 * Idempotent seeder untuk dataset UAT Competition KJA Event Manager.
 *
 * Event khusus: "UAT Competition 2026" (event_type competition, status aktif).
 *
 * Prinsip:
 * - Hanya menyentuh data bermarker UAT (event, kategori, kelas, peserta, tim,
 *   desa/kelompok, dan seluruh subtree competition-nya).
 * - Reuse model + service existing sebagai source of truth: registration lewat
 *   CompetitionRegistrationService, team via CompetitionTeamFormationService,
 *   heat/format via CompetitionHeatManagerService, bracket seeding via
 *   CompetitionBracketSeederService. Tidak ada duplikasi business logic.
 * - Idempotent: updateOrCreate/firstOrCreate + guard keberadaan schedule/team.
 * - Tidak membuat hasil otomatis (kecuali scenario "Existing Result Protection"
 *   yang sengaja diberi hasil untuk menguji guard `has_results`).
 * - Reset aman lewat `php artisan competition:uat-reset` (hanya data UAT).
 */
class CompetitionUatSeeder extends Seeder
{
    public const EVENT_NAME = 'UAT Competition 2026';

    public const EVENT_SLUG = 'uat-competition-2026';

    public const PERSON_PREFIX = 'UAT Peserta ';

    public const TEAM_PREFIX = 'UAT Futsal Team ';

    public const DESA_PREFIX = 'UAT Desa ';

    public const KELOMPOK_PREFIX = 'UAT Kelompok ';

    /**
     * Jumlah peserta per kategori usia (1..N = index person 1-based).
     */
    private const AGE_POOLS = [
        'PAUD' => [1, 10, '2021-06-01'],
        'SD' => [11, 24, '2016-06-01'],
        'SMP' => [25, 38, '2013-06-01'],
        'SMA' => [39, 52, '2010-06-01'],
        'Dewasa' => [53, 60, '2003-06-01'],
    ];

    public function run(): void
    {
        $event = $this->ensureEvent();

        $desaIds = $this->ensureDesa();
        $futsalKelompok = $this->ensureFutsalKelompok($desaIds);
        $otherKelompok = $this->ensureOtherKelompok($desaIds);

        $persons = $this->ensurePersons($desaIds, $futsalKelompok, $otherKelompok);

        $categories = $this->ensureCategories($event);
        $classes = $this->ensureClasses($event, $categories);

        $this->registerParticipants($event, $persons, $classes);

        $this->formTeams($event, $classes['team_vs_team'], $futsalKelompok);
        $this->formTeams($event, $classes['team_heat'], $futsalKelompok);

        $this->prepareHeatScenarios($event, $persons, $classes, $futsalKelompok);

        $this->outputSummary($event);
    }

    // -------------------------------------------------------------------------
    // Master
    // -------------------------------------------------------------------------

    private function ensureEvent(): Event
    {
        return Event::updateOrCreate(
            ['slug' => self::EVENT_SLUG],
            [
                'name' => self::EVENT_NAME,
                'event_type' => 'competition',
                'status' => 'active',
                'description' => 'Event dummy khusus UAT Competition — seluruh data bermarker UAT.',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(14)->toDateString(),
            ],
        );
    }

    private function ensureDesa(): array
    {
        $ids = [];

        for ($i = 1; $i <= 3; $i++) {
            $ids[] = desa::firstOrCreate(['desa_asal' => self::DESA_PREFIX.str_pad((string) $i, 2, '0', STR_PAD_LEFT)])->id;
        }

        return $ids;
    }

    /**
     * @return array<int, kelompok> 1..8 (satu tim futsal = satu kelompok)
     */
    private function ensureFutsalKelompok(array $desaIds): array
    {
        $kelompok = [];

        for ($i = 1; $i <= 8; $i++) {
            $kelompok[$i] = kelompok::firstOrCreate(
                ['kelompok_asal' => self::TEAM_PREFIX.str_pad((string) $i, 2, '0', STR_PAD_LEFT)],
                ['desa_id' => $desaIds[($i - 1) % count($desaIds)]],
            );
        }

        return $kelompok;
    }

    /**
     * @return array<int, kelompok> 1..6
     */
    private function ensureOtherKelompok(array $desaIds): array
    {
        $kelompok = [];

        for ($i = 1; $i <= 6; $i++) {
            $kelompok[$i] = kelompok::firstOrCreate(
                ['kelompok_asal' => self::KELOMPOK_PREFIX.str_pad((string) $i, 2, '0', STR_PAD_LEFT)],
                ['desa_id' => $desaIds[$i % count($desaIds)]],
            );
        }

        return $kelompok;
    }

    /**
     * Buat 60 Person dummy + Participation (via CompetitionRegistrationService
     * pada registrasi pertama). Person 1..32 adalah anggota 8 kelompok futsal
     * (kelompok 1..8), sisanya masuk kelompok UAT Kelompok 01..06.
     *
     * @return array<int, Person> keyed 1..60
     */
    private function ensurePersons(array $desaIds, array $futsalKelompok, array $otherKelompok): array
    {
        $persons = [];

        for ($i = 1; $i <= 60; $i++) {
            $nama = self::PERSON_PREFIX.str_pad((string) $i, 3, '0', STR_PAD_LEFT);

            $kelompokId = $i <= 32
                ? $futsalKelompok[(($i - 1) % 8) + 1]->id
                : $otherKelompok[(($i - 1) % 6) + 1]->id;

            $birth = $this->birthDateFor($i);

            $persons[$i] = Person::updateOrCreate(
                ['nama' => $nama],
                [
                    'jenis_kelamin' => $i % 2 === 0 ? 'P' : 'L',
                    'desa_id' => $desaIds[($i - 1) % count($desaIds)],
                    'kelompok_id' => $kelompokId,
                    'tanggal_lahir' => $birth,
                ],
            );
        }

        return $persons;
    }

    private function birthDateFor(int $i): string
    {
        foreach (self::AGE_POOLS as [$start, $end, $base]) {
            if ($i >= $start && $i <= $end) {
                return date('Y-m-d', strtotime($base.' -'.(($i - $start) % 365).' days'));
            }
        }

        return '2003-06-01';
    }

    /**
     * @return array<string, CompetitionCategory>
     */
    private function ensureCategories(Event $event): array
    {
        $categories = [];

        foreach ([['PAUD', 1], ['SD', 2], ['SMP', 3], ['SMA', 4], ['Dewasa', 5], ['Beregu', 6]] as [$name, $order]) {
            $categories[$name] = CompetitionCategory::updateOrCreate(
                ['event_id' => $event->id, 'name' => 'UAT - '.$name],
                ['code' => 'uat-'.strtolower($name), 'sort_order' => $order, 'is_active' => true],
            );
        }

        return $categories;
    }

    /**
     * @return array<string, CompetitionClass>
     */
    private function ensureClasses(Event $event, array $categories): array
    {
        $make = function (string $key, string $name, string $format, string $categoryKey, string $gender, string $resultType) use ($event, $categories): CompetitionClass {
            return CompetitionClass::updateOrCreate(
                ['event_id' => $event->id, 'name' => $name],
                [
                    'code' => 'uat-'.strtolower(str_replace([' ', '/'], '-', $name)),
                    'competition_category_id' => $categories[$categoryKey]->id,
                    'gender' => $gender,
                    'format' => $format,
                    'status' => CompetitionStatus::REGISTRATION_OPEN,
                    'result_type' => $resultType,
                    'sort_order' => array_search($format, CompetitionFormat::ALL, true) * 10,
                    'is_active' => true,
                ],
            );
        };

        return [
            // Cabang A — Silat UAT (individual_heat / time), heat 5/2
            'heat_5' => $make('heat_5', 'UAT - Heat 5/2 - 5 Peserta', CompetitionFormat::INDIVIDUAL_HEAT, 'SD', 'M', CompetitionResultType::TIME),
            'heat_9' => $make('heat_9', 'UAT - Heat 5/2 - 9 Peserta', CompetitionFormat::INDIVIDUAL_HEAT, 'SMP', 'M', CompetitionResultType::TIME),
            'heat_10' => $make('heat_10', 'UAT - Heat 5/2 - 10 Peserta', CompetitionFormat::INDIVIDUAL_HEAT, 'SMA', 'M', CompetitionResultType::TIME),
            'heat_4' => $make('heat_4', 'UAT - Heat 5/2 - 4 Peserta', CompetitionFormat::INDIVIDUAL_HEAT, 'PAUD', 'M', CompetitionResultType::TIME),
            // Rebuild + existing result protection
            'heat_rebuild' => $make('heat_rebuild', 'UAT - Heat Rebuild', CompetitionFormat::INDIVIDUAL_HEAT, 'SMP', 'M', CompetitionResultType::TIME),
            'heat_protection' => $make('heat_protection', 'UAT - Existing Result Protection', CompetitionFormat::INDIVIDUAL_HEAT, 'SMP', 'M', CompetitionResultType::TIME),
            // Round advancement: R1 (5/2) -> R2 (4/2) -> R3 (2/1)
            'heat_advance' => $make('heat_advance', 'UAT - Round Advancement', CompetitionFormat::INDIVIDUAL_HEAT, 'SMA', 'M', CompetitionResultType::TIME),
            // Cabang B — Silat Putri (P) edge case
            'silat_putri' => $make('silat_putri', 'UAT - Silat Putri', CompetitionFormat::INDIVIDUAL_HEAT, 'Dewasa', 'P', CompetitionResultType::TIME),
            // Result types: time, score, ranking
            'time' => $make('time', 'UAT - Time', CompetitionFormat::INDIVIDUAL_HEAT, 'SD', 'M', CompetitionResultType::TIME),
            'score' => $make('score', 'UAT - Score', CompetitionFormat::INDIVIDUAL_HEAT, 'SMP', 'M', CompetitionResultType::SCORE),
            'ranking' => $make('ranking', 'UAT - Ranking', CompetitionFormat::INDIVIDUAL_MASS, 'Dewasa', 'M', CompetitionResultType::RANKING),
            // Bracket (vs)
            'bracket' => $make('bracket', 'UAT - Bracket', CompetitionFormat::INDIVIDUAL_VS_INDIVIDUAL, 'SMA', 'M', CompetitionResultType::SCORE),
            // Team (Futsal)
            'team_vs_team' => $make('team_vs_team', 'UAT - Team Competition', CompetitionFormat::TEAM_VS_TEAM, 'Beregu', 'M', CompetitionResultType::WIN_LOSS),
            'team_heat' => $make('team_heat', 'UAT - Team Heat', CompetitionFormat::TEAM_HEAT, 'Beregu', 'M', CompetitionResultType::TIME),
        ];
    }

    // -------------------------------------------------------------------------
    // Registration & Teams
    // -------------------------------------------------------------------------

    private function registerParticipants(Event $event, array $persons, array $classes): void
    {
        $regService = app(CompetitionRegistrationService::class);

        $plan = [
            'heat_5' => [11, 12, 13, 14, 15],
            'heat_9' => [25, 26, 27, 28, 29, 30, 31, 32, 33],
            'heat_10' => [39, 40, 41, 42, 43, 44, 45, 46, 47, 48],
            'heat_4' => [1, 2, 3, 4],
            'heat_rebuild' => [34, 35, 36, 37, 38],
            'heat_protection' => [25, 26, 27, 28, 29],
            'heat_advance' => [49, 50, 51, 52, 53, 54, 55, 56, 57],
            'silat_putri' => [50, 52, 54, 56, 58, 60],
            'time' => [16, 17, 18, 19, 20, 21],
            'score' => [25, 26, 27, 28, 29, 30],
            'ranking' => [49, 51, 53, 54, 55, 56, 57, 58, 59, 60],
            'bracket' => [39, 40, 41, 42, 43, 44, 45, 46],
            'team_vs_team' => range(1, 32),
            'team_heat' => range(1, 32),
        ];

        foreach ($plan as $key => $indexes) {
            foreach ($indexes as $index) {
                $person = $persons[$index];

                if ($this->isRegistered($event, $person, $classes[$key])) {
                    continue;
                }

                try {
                    $regService->registerForPerson(
                        person: $person,
                        eventId: $event->id,
                        competitionCategoryId: $classes[$key]->competition_category_id,
                        competitionClassId: $classes[$key]->id,
                    );
                } catch (ValidationException) {
                }
            }
        }
    }

    private function isRegistered(Event $event, Person $person, CompetitionClass $class): bool
    {
        $participation = $person->participations()->where('event_id', $event->id)->first();

        return $participation !== null
            && CompetitionRegistration::where('competition_class_id', $class->id)
                ->where('participation_id', $participation->id)
                ->exists();
    }

    private function formTeams(Event $event, CompetitionClass $class, array $futsalKelompok): void
    {
        if (CompetitionTeam::where('competition_class_id', $class->id)->exists()) {
            return;
        }

        app(CompetitionTeamFormationService::class)->formForClass(
            eventId: $event->id,
            competitionClassId: $class->id,
            forcedTeamSize: 4,
        );
    }

    // -------------------------------------------------------------------------
    // Heat scenarios
    // -------------------------------------------------------------------------

    private function prepareHeatScenarios(Event $event, array $persons, array $classes, array $futsalKelompok): void
    {
        $heatManager = app(CompetitionHeatManagerService::class);

        // Format per round (5/2 secara default).
        foreach (['heat_5', 'heat_9', 'heat_10', 'heat_4', 'heat_rebuild', 'heat_protection', 'silat_putri', 'time', 'score'] as $key) {
            $this->upsertFormat($heatManager, $event, $classes[$key], round: 1, perHeat: 5, qualifiers: 2);
        }

        // Case A: 5 -> 1 heat of 5 (round_exists toleran)
        $this->generateRoundSafe($heatManager, $event, $classes['heat_5'], 1);
        // Case B: 9 -> 2 heat [5,4]
        $this->generateRoundSafe($heatManager, $event, $classes['heat_9'], 1);
        // Case C: 10 -> 2 heat [5,5]
        $this->generateRoundSafe($heatManager, $event, $classes['heat_10'], 1);
        // Case D: 4 -> 1 heat of 4 (bukan 2+2)
        $this->generateRoundSafe($heatManager, $event, $classes['heat_4'], 1);

        // Time: 6 -> 2 heat [5,1]
        $this->generateRoundSafe($heatManager, $event, $classes['time'], 1);
        // Score: 6 -> 2 heat [5,1]; ranking asc default? Score = desc (higher wins).
        $this->generateRoundSafe($heatManager, $event, $classes['score'], 1);
        // Silat Putri: 6 (P) -> 2 heat [5,1]
        $this->generateRoundSafe($heatManager, $event, $classes['silat_putri'], 1);

        // Rebuild scenario: legacy round = required_participants 2 (2,2,1), no results.
        $this->createLegacyRebuildRound($event, $classes['heat_rebuild']);

        // Existing result protection: legacy round required 2 + hasil heat terisi.
        $this->createLegacyProtectionRound($event, $classes['heat_protection']);

        // Round advancement: define formats R1..R3, generate R1 only.
        $this->upsertFormat($heatManager, $event, $classes['heat_advance'], 1, 5, 2);
        $this->upsertFormat($heatManager, $event, $classes['heat_advance'], 2, 4, 2);
        $this->upsertFormat($heatManager, $event, $classes['heat_advance'], 3, 2, 1);
        $this->generateRoundSafe($heatManager, $event, $classes['heat_advance'], 1);

        // Team heat: 8 teams -> format 5/2 -> 2 heat [5,3]
        $this->upsertFormat($heatManager, $event, $classes['team_heat'], 1, 5, 2);
        $this->generateRoundSafe($heatManager, $event, $classes['team_heat'], 1);

        // Ranking (mass): 1 schedule + semua participant sebagai entries (tanpa hasil).
        $this->prepareMassSchedule($event, $classes['ranking']);

        // Bracket: individual vs & team vs (QF -> SF -> Final).
        $this->prepareBracket($event, $classes['bracket']);
        $this->prepareBracket($event, $classes['team_vs_team']);
    }

    private function upsertFormat(CompetitionHeatManagerService $heatManager, Event $event, CompetitionClass $class, int $round, int $perHeat, int $qualifiers): void
    {
        $heatManager->upsertFormat(
            eventId: $event->id,
            classId: $class->id,
            round: $round,
            participantsPerHeat: $perHeat,
            qualifiersPerHeat: $qualifiers,
        );
    }

    private function generateRoundSafe(CompetitionHeatManagerService $heatManager, Event $event, CompetitionClass $class, int $round): void
    {
        $heatManager->generateRound($event->id, $class->id, $round);
    }

    /**
     * Legacy round (round 1) dengan required_participants=2 — persis kondisi
     * sebelum Heat Manager: heat dibuat di luar Heat Manager. Tanpa hasil.
     * needs_rebuild = true (2 !== 5). Round tidak dimulai (Scheduled).
     */
    private function createLegacyRebuildRound(Event $event, CompetitionClass $class): void
    {
        $existing = CompetitionSchedule::where('competition_class_id', $class->id)
            ->where(function ($q) {
                $q->whereNull('sort_order')->orWhere('sort_order', '<', 200);
            })
            ->exists();

        if ($existing) {
            return;
        }

        $registrations = CompetitionRegistration::where('competition_class_id', $class->id)
            ->with('participation')
            ->orderBy('id')
            ->get();

        foreach ($registrations->chunk(2) as $chunkIndex => $chunk) {
            $schedule = CompetitionSchedule::create([
                'competition_class_id' => $class->id,
                'status' => 'Scheduled',
                'required_participants' => 2,
                'sort_order' => 100 + ($chunkIndex + 1),
            ]);

            $order = 1;
            foreach ($chunk as $registration) {
                CompetitionScheduleEntry::create([
                    'competition_schedule_id' => $schedule->id,
                    'competition_registration_id' => $registration->id,
                    'order_number' => $order++,
                ]);
            }
        }
    }

    /**
     * Seperti legacy rebuild, tapi hasil heat SUDAH diinput (competition_heat_results)
     * sehingga guard `has_results` memblokir rebuild. Label jelas di UI.
     */
    private function createLegacyProtectionRound(Event $event, CompetitionClass $class): void
    {
        $existing = CompetitionSchedule::where('competition_class_id', $class->id)
            ->where(function ($q) {
                $q->whereNull('sort_order')->orWhere('sort_order', '<', 200);
            })
            ->exists();

        if ($existing) {
            return;
        }

        $registrations = CompetitionRegistration::where('competition_class_id', $class->id)
            ->orderBy('id')
            ->get();

        foreach ($registrations->chunk(2) as $chunkIndex => $chunk) {
            $schedule = CompetitionSchedule::create([
                'competition_class_id' => $class->id,
                'status' => 'Scheduled',
                'required_participants' => 2,
                'sort_order' => 100 + ($chunkIndex + 1),
            ]);

            $order = 1;
            foreach ($chunk as $registration) {
                CompetitionScheduleEntry::create([
                    'competition_schedule_id' => $schedule->id,
                    'competition_registration_id' => $registration->id,
                    'order_number' => $order++,
                ]);

                CompetitionHeatResult::create([
                    'competition_schedule_id' => $schedule->id,
                    'competition_registration_id' => $registration->id,
                    'score' => $order * 10.0,
                    'position' => null,
                    'status' => 'Lolos',
                ]);
            }
        }
    }

    private function prepareMassSchedule(Event $event, CompetitionClass $class): void
    {
        if (CompetitionSchedule::where('competition_class_id', $class->id)->exists()) {
            return;
        }

        $registrations = CompetitionRegistration::where('competition_class_id', $class->id)
            ->orderBy('id')
            ->get();

        $schedule = CompetitionSchedule::create([
            'competition_class_id' => $class->id,
            'status' => 'Scheduled',
            'required_participants' => $registrations->count(),
            'sort_order' => 1,
        ]);

        foreach ($registrations as $index => $registration) {
            CompetitionScheduleEntry::create([
                'competition_schedule_id' => $schedule->id,
                'competition_registration_id' => $registration->id,
                'order_number' => $index + 1,
            ]);
        }
    }

    /**
     * Bracket single-elimination (QF -> SF -> Final) untuk format VS.
     * Struktur dibuat langsung (data seeding) lalu di-seed via
     * CompetitionBracketSeederService (reuse service existing).
     */
    private function prepareBracket(Event $event, CompetitionClass $class): void
    {
        $existing = CompetitionBracket::where('competition_class_id', $class->id)
            ->whereIn('status', ['draft', 'active'])
            ->exists();

        if ($existing) {
            return;
        }

        $competitorCount = $class->isTeamFormat()
            ? CompetitionTeam::where('competition_class_id', $class->id)->count()
            : CompetitionRegistration::where('competition_class_id', $class->id)->count();

        $size = $this->suggestBracketSize($competitorCount);

        $bracket = CompetitionBracket::create([
            'competition_class_id' => $class->id,
            'name' => $class->name.' Bracket',
            'participant_count' => $size,
            'status' => 'active',
        ]);

        $totalRounds = (int) log($size, 2);

        for ($round = $totalRounds; $round >= 1; $round--) {
            $matchesInRound = (int) pow(2, $round - 1);

            for ($pos = 1; $pos <= $matchesInRound; $pos++) {
                $schedule = CompetitionSchedule::create([
                    'competition_class_id' => $class->id,
                    'status' => 'Scheduled',
                    'required_participants' => 2,
                    'sort_order' => ($totalRounds - $round) * 100 + $pos,
                ]);

                $bracketMatch = new CompetitionBracketMatch([
                    'competition_bracket_id' => $bracket->id,
                    'competition_schedule_id' => $schedule->id,
                    'round' => $round,
                    'position' => $pos,
                ]);

                if ($round < $totalRounds) {
                    $prevMatchesCount = (int) pow(2, $round);
                    $bracketMatch->source_match_a_id = $this->findBracketMatch($bracket->id, $round + 1, $pos * 2 - 1);
                    $bracketMatch->source_match_b_id = $this->findBracketMatch($bracket->id, $round + 1, $pos * 2);
                }

                $bracketMatch->save();
            }
        }

        app(CompetitionBracketSeederService::class)->seedInitialRound($event->id, $bracket->id);
    }

    private function findBracketMatch(int $bracketId, int $round, int $position): ?int
    {
        return CompetitionBracketMatch::where('competition_bracket_id', $bracketId)
            ->where('round', $round)
            ->where('position', $position)
            ->value('id');
    }

    private function suggestBracketSize(int $participantCount): int
    {
        if ($participantCount <= 4) {
            return 4;
        }
        if ($participantCount <= 8) {
            return 8;
        }
        if ($participantCount <= 16) {
            return 16;
        }

        return 32;
    }

    private function outputSummary(Event $event): void
    {
        $this->command?->line('');
        $this->command?->info('===== UAT COMPETITION SEED SUMMARY =====');

        $classIds = CompetitionClass::where('event_id', $event->id)->pluck('id');
        $events = Event::where('slug', self::EVENT_SLUG)->count();
        $persons = Person::where('nama', 'like', self::PERSON_PREFIX.'%')->count();
        $teams = CompetitionTeam::where('event_id', $event->id)->count();
        $categories = CompetitionCategory::where('event_id', $event->id)->count();
        $classes = $classIds->count();
        $formats = CompetitionHeatFormat::whereIn('competition_class_id', $classIds)->count();
        $schedules = CompetitionSchedule::whereIn('competition_class_id', $classIds)->count();
        $entries = CompetitionScheduleEntry::whereIn('competition_schedule_id', CompetitionSchedule::whereIn('competition_class_id', $classIds)->pluck('id'))->count();

        $this->command?->info("Event UAT Competition 2026 ......... {$events}");
        $this->command?->info("Persons (UAT Peserta) ............. {$persons}");
        $this->command?->info("Teams (UAT Futsal) ................ {$teams}");
        $this->command?->info("Categories (UAT - ...) ............. {$categories}");
        $this->command?->info("Classes ............................ {$classes}");
        $this->command?->info("Heat formats ....................... {$formats}");
        $this->command?->info("Schedules (heats/matches/mass) ..... {$schedules}");
        $this->command?->info("Schedule entries ................... {$entries}");
        $this->command?->info('===== END =====');
    }
}
