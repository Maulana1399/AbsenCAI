<?php

use App\Models\Event;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

function auditLegacy_makePeserta(array $overrides = []): peserta
{
    return peserta::create(array_merge([
        'nama' => 'Test Person',
        'jenis_kelamin' => 'Laki - Laki',
    ], $overrides));
}

// ---------------------------------------------------------------------------
// Basic execution
// ---------------------------------------------------------------------------

test('command exits successfully with empty database', function () {
    $this->artisan('audit:legacy-data')
        ->assertExitCode(0);
});

test('command reports zero writes', function () {
    $this->artisan('audit:legacy-data')
        ->expectsOutputToContain('Database writes performed: 0')
        ->assertExitCode(0);
});

test('command does not modify peserta count', function () {
    auditLegacy_makePeserta();

    $before = peserta::count();

    $this->artisan('audit:legacy-data')
        ->assertExitCode(0);

    expect(peserta::count())->toBe($before);
});

test('command does not create people', function () {
    auditLegacy_makePeserta();

    $this->artisan('audit:legacy-data')
        ->assertExitCode(0);

    expect(Person::count())->toBe(0);
});

test('command does not create participations', function () {
    auditLegacy_makePeserta();

    $this->artisan('audit:legacy-data')
        ->assertExitCode(0);

    expect(Participation::count())->toBe(0);
});

test('command does not create events', function () {
    $this->artisan('audit:legacy-data')
        ->assertExitCode(0);

    expect(Event::count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Summary output
// ---------------------------------------------------------------------------

test('command reports total peserta count', function () {
    auditLegacy_makePeserta(['nama' => 'Alpha']);
    auditLegacy_makePeserta(['nama' => 'Beta']);

    $this->artisan('audit:legacy-data')
        ->expectsOutputToContain('total peserta: 2')
        ->assertExitCode(0);
});

test('command reports blank nama', function () {
    peserta::create([
        'nama' => '',
        'nip' => 1001,
    ]);

    $this->artisan('audit:legacy-data')
        ->expectsOutputToContain('blank/null nama: 1')
        ->assertExitCode(0);
});

test('command reports null participant_number', function () {
    auditLegacy_makePeserta(['participant_number' => null]);

    $this->artisan('audit:legacy-data')
        ->expectsOutputToContain('null participant_number')
        ->assertExitCode(0);
});

test('command reports null attendance_code', function () {
    auditLegacy_makePeserta(['attendance_code' => null]);

    $this->artisan('audit:legacy-data')
        ->expectsOutputToContain('null attendance_code')
        ->assertExitCode(0);
});

// ---------------------------------------------------------------------------
// Gender variants
// ---------------------------------------------------------------------------

test('command reports all gender variants without modifying them', function () {
    peserta::create(['nama' => 'A', 'nip' => 1001, 'jenis_kelamin' => 'Laki - Laki']);
    peserta::create(['nama' => 'B', 'nip' => 1002, 'jenis_kelamin' => 'Laki-laki']);
    peserta::create(['nama' => 'C', 'nip' => 1003, 'jenis_kelamin' => 'Laki laki']);
    peserta::create(['nama' => 'D', 'nip' => 1004, 'jenis_kelamin' => 'L']);
    peserta::create(['nama' => 'E', 'nip' => 2001, 'jenis_kelamin' => 'Perempuan']);
    peserta::create(['nama' => 'F', 'nip' => 2002, 'jenis_kelamin' => 'P']);
    peserta::create(['nama' => 'G', 'nip' => 2003, 'jenis_kelamin' => null]);

    $this->artisan('audit:legacy-data')
        ->expectsOutputToContain("'Laki - Laki'")
        ->expectsOutputToContain("'Laki-laki'")
        ->expectsOutputToContain("'Laki laki'")
        ->expectsOutputToContain("'L'")
        ->expectsOutputToContain("'Perempuan'")
        ->expectsOutputToContain("'P'")
        ->expectsOutputToContain('NULL')
        ->assertExitCode(0);

    $dbGender = peserta::where('nama', 'A')->first()->jenis_kelamin;
    expect($dbGender)->toBe('Laki - Laki');
});

test('command normalizes common male variants to L', function () {
    peserta::create(['nama' => 'A', 'nip' => 1001, 'jenis_kelamin' => 'Laki - Laki']);
    peserta::create(['nama' => 'B', 'nip' => 1002, 'jenis_kelamin' => 'Laki-laki']);
    peserta::create(['nama' => 'C', 'nip' => 1003, 'jenis_kelamin' => 'Laki laki']);
    peserta::create(['nama' => 'D', 'nip' => 1004, 'jenis_kelamin' => 'L']);

    $this->artisan('audit:legacy-data')
        ->expectsOutputToContain('→ L')
        ->assertExitCode(0);
});

test('command normalizes common female variants to P', function () {
    peserta::create(['nama' => 'E', 'nip' => 2001, 'jenis_kelamin' => 'Perempuan']);
    peserta::create(['nama' => 'F', 'nip' => 2002, 'jenis_kelamin' => 'P']);

    $this->artisan('audit:legacy-data')
        ->expectsOutputToContain('→ P')
        ->assertExitCode(0);
});

test('command does not alter stored gender values', function () {
    peserta::create(['nama' => 'A', 'nip' => 1001, 'jenis_kelamin' => 'Laki - Laki']);

    $this->artisan('audit:legacy-data')->assertExitCode(0);

    $this->assertDatabaseHas('pesertas', [
        'nama' => 'A',
        'jenis_kelamin' => 'Laki - Laki',
    ]);
});

// ---------------------------------------------------------------------------
// Name / identity quality
// ---------------------------------------------------------------------------

test('command reports duplicate normalized names', function () {
    peserta::create(['nama' => 'Budi Santoso', 'nip' => 1001]);
    peserta::create(['nama' => 'Budi Santoso', 'nip' => 1002]);

    $exitCode = Artisan::call('audit:legacy-data');
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('budi santoso')
        ->and($output)->toContain('count=2');
});

test('command reports duplicate name + desa_id', function () {
    $desa = \App\Models\desa::create(['desa_asal' => 'Test Village']);
    $kel1 = \App\Models\kelompok::create(['kelompok_asal' => 'A', 'desa_id' => $desa->id]);
    $kel2 = \App\Models\kelompok::create(['kelompok_asal' => 'B', 'desa_id' => $desa->id]);

    peserta::create(['nama' => 'Siti Aminah', 'nip' => 1001, 'desa_id' => $desa->id, 'kelompok_id' => $kel1->id]);
    peserta::create(['nama' => 'Siti Aminah', 'nip' => 1002, 'desa_id' => $desa->id, 'kelompok_id' => $kel2->id]);

    $exitCode = Artisan::call('audit:legacy-data');
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('siti aminah | desa_id='.$desa->id)
        ->and($output)->toContain('count=2');
});

test('command reports conflicting gender for same name + desa', function () {
    $desa = \App\Models\desa::create(['desa_asal' => 'Test Village']);
    $kel1 = \App\Models\kelompok::create(['kelompok_asal' => 'A', 'desa_id' => $desa->id]);
    $kel2 = \App\Models\kelompok::create(['kelompok_asal' => 'B', 'desa_id' => $desa->id]);

    peserta::create(['nama' => 'Alex', 'nip' => 1001, 'jenis_kelamin' => 'Laki - Laki', 'desa_id' => $desa->id, 'kelompok_id' => $kel1->id]);
    peserta::create(['nama' => 'Alex', 'nip' => 2001, 'jenis_kelamin' => 'Perempuan', 'desa_id' => $desa->id, 'kelompok_id' => $kel2->id]);

    $exitCode = Artisan::call('audit:legacy-data');
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('alex | desa_id='.$desa->id)
        ->and($output)->toContain('Laki - Laki')
        ->and($output)->toContain('Perempuan');
});

// ---------------------------------------------------------------------------
// New domain state
// ---------------------------------------------------------------------------

test('command reports new domain counts as zero when empty', function () {
    $this->artisan('audit:legacy-data')
        ->expectsOutputToContain('total people: 0')
        ->expectsOutputToContain('total participations: 0')
        ->expectsOutputToContain('total events: 0')
        ->assertExitCode(0);
});

test('command reports existing new domain records', function () {
    $person = Person::create(['nama' => 'Existing Person', 'nip' => 5001]);
    $event = Event::create(['name' => 'Test Event', 'slug' => 'test-event', 'status' => 'active']);
    Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);

    $this->artisan('audit:legacy-data')
        ->expectsOutputToContain('total people: 1')
        ->expectsOutputToContain('total participations: 1')
        ->expectsOutputToContain('total events: 1')
        ->assertExitCode(0);
});

// ---------------------------------------------------------------------------
// Legacy event state
// ---------------------------------------------------------------------------

test('command reports legacy event not found when missing', function () {
    $this->artisan('audit:legacy-data')
        ->expectsOutputToContain('exists: no')
        ->expectsOutputToContain("slug 'cai-operational' not found")
        ->assertExitCode(0);
});

test('command reports legacy event details when found', function () {
    Event::create([
        'name' => 'CAI Operational',
        'slug' => 'cai-operational',
        'status' => 'active',
    ]);

    $this->artisan('audit:legacy-data')
        ->expectsOutputToContain('exists: yes')
        ->expectsOutputToContain('slug: cai-operational')
        ->expectsOutputToContain('status: active')
        ->assertExitCode(0);
});

// ---------------------------------------------------------------------------
// Idempotency / safety
// ---------------------------------------------------------------------------

test('command is idempotent', function () {
    auditLegacy_makePeserta();

    $this->artisan('audit:legacy-data')->assertExitCode(0);
    $this->artisan('audit:legacy-data')->assertExitCode(0);
    $this->artisan('audit:legacy-data')->assertExitCode(0);

    expect(peserta::count())->toBe(1);
    expect(Person::count())->toBe(0);
    expect(Participation::count())->toBe(0);
});

test('command handles whitespace-only names', function () {
    peserta::create([
        'nama' => '  ',
        'nip' => 1001,
    ]);

    $this->artisan('audit:legacy-data')
        ->expectsOutputToContain('blank/null nama')
        ->assertExitCode(0);
});

test('command handles peserta with all nullable fields null', function () {
    peserta::create([
        'nama' => 'Minimal',
        'nip' => 9999,
        'participant_number' => null,
        'attendance_code' => null,
        'jenis_kelamin' => null,
        'desa_id' => null,
    ]);

    $this->artisan('audit:legacy-data')
        ->assertExitCode(0);
});
