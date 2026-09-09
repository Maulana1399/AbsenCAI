<?php

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
use App\Models\Event;
use App\Models\Participation;
use App\Models\Person;
use App\Services\Competition\CompetitionHeatManagerService;
use App\Services\Competition\CompetitionMultiRoundHeatService;
use Database\Seeders\CompetitionUatSeeder;
use Illuminate\Support\Facades\Artisan;

// ---------------------------------------------------------------------------
// UAT dataset sanity (seeder). Dataset dijalankan pada in-memory DB.
// ---------------------------------------------------------------------------

function uat_event(): Event
{
    return Event::where('slug', CompetitionUatSeeder::EVENT_SLUG)->firstOrFail();
}

function uat_class(string $name): CompetitionClass
{
    return CompetitionClass::where('event_id', uat_event()->id)->where('name', $name)->firstOrFail();
}

function uat_class_schedules(CompetitionClass $class): \Illuminate\Support\Collection
{
    return app(CompetitionMultiRoundHeatService::class)->roundSchedules($class->id, 1);
}

beforeEach(function () {
    $this->seed(CompetitionUatSeeder::class);
});

test('event UAT Competition 2026 dibuat dan aktif', function () {
    $event = uat_event();

    expect($event->status)->toBe('active')
        ->and($event->event_type)->toBe('competition')
        ->and($event->isActive())->toBeTrue();
});

test('60 peserta UAT dengan identitas lengkap', function () {
    $persons = Person::where('nama', 'like', CompetitionUatSeeder::PERSON_PREFIX.'%')->get();

    expect($persons)->toHaveCount(60);

    $participations = Participation::whereIn('person_id', $persons->pluck('id'))->get();

    expect($participations)->toHaveCount(60);

    foreach ($participations as $participation) {
        expect(trim((string) $participation->participant_number))->not->toBeEmpty()
            ->and(trim((string) $participation->attendance_code))->not->toBeEmpty()
            ->and($participation->jenis_peserta)->toBe('Peserta');
    }

    $females = $persons->where('jenis_kelamin', 'P')->count();

    expect($females)->toBeGreaterThanOrEqual(25)
        ->and($persons->where('jenis_kelamin', 'L')->count())->toBeGreaterThanOrEqual(25);
});

test('kategori PAUD/SD/SMP/SMA/Dewasa + Beregu dibuat', function () {
    $names = CompetitionCategory::where('event_id', uat_event()->id)->pluck('name')->all();

    foreach (['UAT - PAUD', 'UAT - SD', 'UAT - SMP', 'UAT - SMA', 'UAT - Dewasa', 'UAT - Beregu'] as $expected) {
        expect($names)->toContain($expected);
    }
});

test('semua scenario class dibuat', function () {
    $names = CompetitionClass::where('event_id', uat_event()->id)->pluck('name')->all();

    foreach ([
        'UAT - Heat 5/2 - 5 Peserta',
        'UAT - Heat 5/2 - 9 Peserta',
        'UAT - Heat 5/2 - 10 Peserta',
        'UAT - Heat 5/2 - 4 Peserta',
        'UAT - Heat Rebuild',
        'UAT - Existing Result Protection',
        'UAT - Round Advancement',
        'UAT - Silat Putri',
        'UAT - Time',
        'UAT - Score',
        'UAT - Ranking',
        'UAT - Bracket',
        'UAT - Team Competition',
        'UAT - Team Heat',
    ] as $expected) {
        expect($names)->toContain($expected);
    }
});

test('Case A: 5 peserta -> 1 heat penuh 5 (top 2)', function () {
    $class = uat_class('UAT - Heat 5/2 - 5 Peserta');

    $schedules = uat_class_schedules($class);

    expect($schedules)->toHaveCount(1)
        ->and((int) $schedules->first()->required_participants)->toBe(5)
        ->and($schedules->first()->scheduleEntries()->count())->toBe(5);

    $format = CompetitionHeatFormat::where('competition_class_id', $class->id)->where('round', 1)->first();

    expect((int) $format->participants_per_heat)->toBe(5)
        ->and((int) $format->qualifiers_per_heat)->toBe(2);
});

test('Case B: 9 peserta -> 2 heat [5,4]', function () {
    $class = uat_class('UAT - Heat 5/2 - 9 Peserta');

    $schedules = uat_class_schedules($class)->values();

    expect($schedules)->toHaveCount(2)
        ->and($schedules->get(0)->scheduleEntries()->count())->toBe(5)
        ->and($schedules->get(1)->scheduleEntries()->count())->toBe(4);

    foreach ($schedules as $schedule) {
        expect((int) $schedule->required_participants)->toBe(5);
    }
});

test('Case C: 10 peserta -> 2 heat [5,5]', function () {
    $class = uat_class('UAT - Heat 5/2 - 10 Peserta');

    $schedules = uat_class_schedules($class)->values();

    expect($schedules)->toHaveCount(2);

    foreach ($schedules as $schedule) {
        expect($schedule->scheduleEntries()->count())->toBe(5)
            ->and((int) $schedule->required_participants)->toBe(5);
    }
});

test('Case D: 4 peserta -> 1 heat isi 4 (bukan 2+2)', function () {
    $class = uat_class('UAT - Heat 5/2 - 4 Peserta');

    $schedules = uat_class_schedules($class);

    expect($schedules)->toHaveCount(1)
        ->and($schedules->first()->scheduleEntries()->count())->toBe(4)
        ->and((int) $schedules->first()->required_participants)->toBe(5);
});

test('Rebuild: legacy 2-participant round terdeteksi dan dibangun ulang ke 5', function () {
    $event = uat_event();
    $class = uat_class('UAT - Heat Rebuild');
    $service = app(CompetitionHeatManagerService::class);

    $schedules = uat_class_schedules($class);

    expect($schedules)->not->toBeEmpty()
        ->and($schedules->every(fn ($s) => (int) $s->required_participants === 2))->toBeTrue()
        ->and($schedules->every(fn ($s) => ! in_array($s->status, ['Playing', 'Waiting Result', 'Finished'], true)))->toBeTrue();

    $format = $service->formatForRound($class->id, 1);

    expect((int) $format->participants_per_heat)->toBe(5);

    $needsRebuild = $schedules->contains(fn ($s) => (int) $s->required_participants !== (int) $format->participants_per_heat);

    expect($needsRebuild)->toBeTrue();

    $result = $service->rebuildRound($event->id, $class->id, 1);

    expect($result['rebuilt'])->toBeTrue();

    $after = uat_class_schedules($class)->values();

    expect($after)->toHaveCount(1)
        ->and((int) $after->first()->required_participants)->toBe(5)
        ->and($after->first()->scheduleEntries()->count())->toBe(5);
});

test('Existing Result Protection: rebuild ditolak karena sudah ada hasil heat', function () {
    $event = uat_event();
    $class = uat_class('UAT - Existing Result Protection');
    $service = app(CompetitionHeatManagerService::class);

    $schedules = uat_class_schedules($class);

    expect($schedules)->not->toBeEmpty();

    $hasResults = CompetitionHeatResult::whereIn('competition_schedule_id', $schedules->pluck('id'))->exists();

    expect($hasResults)->toBeTrue();

    $result = $service->rebuildRound($event->id, $class->id, 1);

    expect($result['rebuilt'])->toBeFalse()
        ->and($result['reason'])->toBe('has_results');
});

test('Round Advancement: format R1/R2/R3 dibuat dan R1 digenerate', function () {
    $class = uat_class('UAT - Round Advancement');
    $service = app(CompetitionHeatManagerService::class);

    foreach ([1 => [5, 2], 2 => [4, 2], 3 => [2, 1]] as $round => [$per, $qual]) {
        $format = $service->formatForRound($class->id, $round);

        expect($format)->not->toBeNull()
            ->and((int) $format->participants_per_heat)->toBe($per)
            ->and((int) $format->qualifiers_per_heat)->toBe($qual);
    }

    $round1Schedules = uat_class_schedules($class)->values();

    expect($round1Schedules)->toHaveCount(2)
        ->and($round1Schedules->get(0)->scheduleEntries()->count())->toBe(5)
        ->and($round1Schedules->get(1)->scheduleEntries()->count())->toBe(4);

    $round2Schedules = \Illuminate\Support\Collection::make()
        ->merge(app(CompetitionMultiRoundHeatService::class)->roundSchedules($class->id, 2))
        ->values();

    expect($round2Schedules)->toBeEmpty();
});

test('Silat Putri: hanya peserta perempuan dan heat terisi sesuai format', function () {
    $class = uat_class('UAT - Silat Putri');

    expect($class->gender)->toBe('P');

    $registrations = CompetitionRegistration::where('competition_class_id', $class->id)
        ->with('participation.person')
        ->get();

    expect($registrations)->toHaveCount(6);

    foreach ($registrations as $registration) {
        expect($registration->participation->person->jenis_kelamin)->toBe('P');
    }

    $schedules = uat_class_schedules($class)->values();

    expect($schedules)->toHaveCount(2)
        ->and($schedules->get(0)->scheduleEntries()->count())->toBe(5)
        ->and($schedules->get(1)->scheduleEntries()->count())->toBe(1);
});

test('Team Competition: 8 tim futsal terbentuk dengan anggota', function () {
    $class = uat_class('UAT - Team Competition');

    $teams = CompetitionTeam::where('competition_class_id', $class->id)->get();

    expect($teams)->toHaveCount(8)
        ->and(CompetitionBracket::where('competition_class_id', $class->id)->exists())->toBeTrue();

    foreach ($teams as $team) {
        expect($team->members()->count())->toBeGreaterThanOrEqual(1);
    }
});

test('Bracket: bracket individual dibentuk dengan initial matches ter-seed', function () {
    $class = uat_class('UAT - Bracket');

    $bracket = CompetitionBracket::where('competition_class_id', $class->id)->first();

    expect($bracket)->not->toBeNull()
        ->and((int) $bracket->participant_count)->toBe(8);

    $initialMatches = CompetitionBracketMatch::where('competition_bracket_id', $bracket->id)
        ->where('round', 3)
        ->get();

    expect($initialMatches)->toHaveCount(4);

    $seededEntries = CompetitionScheduleEntry::whereIn('competition_schedule_id', $initialMatches->pluck('competition_schedule_id'))->count();

    expect($seededEntries)->toBe(8);
});

test('tidak ada hasil otomatis untuk skenario non-proteksi', function () {
    $classIds = CompetitionClass::where('event_id', uat_event()->id)->pluck('id');

    $heatResultCount = CompetitionHeatResult::whereIn('competition_schedule_id', CompetitionSchedule::whereIn('competition_class_id', $classIds)->pluck('id'))->count();

    $protectionScheduleIds = CompetitionSchedule::where('competition_class_id', uat_class('UAT - Existing Result Protection')->id)->pluck('id');
    $protectionResults = CompetitionHeatResult::whereIn('competition_schedule_id', $protectionScheduleIds)->count();

    expect($heatResultCount)->toBe($protectionResults)
        ->and($protectionResults)->toBeGreaterThan(0);
});

test('seeder idempotent: menjalankan dua kali tidak menggandakan data', function () {
    $eventBefore = uat_event();
    $countBefore = [
        'classes' => CompetitionClass::where('event_id', $eventBefore->id)->count(),
        'schedules' => CompetitionSchedule::whereIn('competition_class_id', CompetitionClass::where('event_id', $eventBefore->id)->pluck('id'))->count(),
        'teams' => CompetitionTeam::where('event_id', $eventBefore->id)->count(),
        'persons' => Person::where('nama', 'like', CompetitionUatSeeder::PERSON_PREFIX.'%')->count(),
    ];

    $this->seed(CompetitionUatSeeder::class);

    $eventAfter = uat_event();

    expect(CompetitionClass::where('event_id', $eventAfter->id)->count())->toBe($countBefore['classes'])
        ->and(CompetitionSchedule::whereIn('competition_class_id', CompetitionClass::where('event_id', $eventAfter->id)->pluck('id'))->count())->toBe($countBefore['schedules'])
        ->and(CompetitionTeam::where('event_id', $eventAfter->id)->count())->toBe($countBefore['teams'])
        ->and(Person::where('nama', 'like', CompetitionUatSeeder::PERSON_PREFIX.'%')->count())->toBe($countBefore['persons']);
});

test('competition:uat-reset menghapus dataset UAT dan mempertahankan event lain', function () {
    $event = uat_event();

    Event::create([
        'name' => 'Event Lain',
        'slug' => 'event-lain-'.str()->random(6),
        'status' => 'active',
    ]);

    $exitCode = Artisan::call('competition:uat-reset', ['--apply' => true]);

    expect($exitCode)->toBe(0)
        ->and(Event::where('slug', CompetitionUatSeeder::EVENT_SLUG)->exists())->toBeFalse()
        ->and(Person::where('nama', 'like', CompetitionUatSeeder::PERSON_PREFIX.'%')->exists())->toBeFalse()
        ->and(Event::where('name', 'Event Lain')->exists())->toBeTrue();
});
