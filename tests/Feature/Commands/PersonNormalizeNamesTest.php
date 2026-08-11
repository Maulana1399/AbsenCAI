<?php

use App\Models\desa;
use App\Models\Event;
use App\Models\Participation;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function pnn_makePerson(array $overrides = []): Person
{
    return Person::create(array_merge([
        'nama' => 'Test Person',
    ], $overrides));
}

function pnn_makeDesa(array $overrides = []): desa
{
    return desa::create(array_merge([
        'desa_asal' => 'Desa Test',
    ], $overrides));
}

function pnn_makeParticipation(Person $person, int $eventId): Participation
{
    return Participation::create([
        'person_id' => $person->id,
        'event_id' => $eventId,
        'participant_number' => 'P-'.$person->id,
        'attendance_code' => 'AC-'.$person->id,
        'jenis_peserta' => 'Wajib',
    ]);
}

// ---------------------------------------------------------------------------
// Dry run / audit (no writes)
// ---------------------------------------------------------------------------

test('dry run exits successfully and reports totals', function () {
    pnn_makePerson(['nama' => 'REFIANTITO']);

    $this->artisan('person:normalize-names')
        ->expectsOutputToContain('total Person: 1')
        ->expectsOutputToContain('names needing change: 1')
        ->expectsOutputToContain('records to update: 1')
        ->expectsOutputToContain('Database writes performed: 0')
        ->assertExitCode(0);
});

test('dry run does not modify any data', function () {
    pnn_makePerson(['nama' => 'REFIANTITO']);

    $this->artisan('person:normalize-names')->assertExitCode(0);

    $this->assertDatabaseHas('people', ['id' => 1, 'nama' => 'REFIANTITO']);
});

test('dry run reports already proper names as unchanged', function () {
    pnn_makePerson(['nama' => 'Refiantito']);

    $this->artisan('person:normalize-names')
        ->expectsOutputToContain('names needing change: 0')
        ->expectsOutputToContain('records to update: 0')
        ->assertExitCode(0);
});

test('special-case names are already canonical and never flagged', function () {
    pnn_makePerson(['nama' => 'Ahmad Agung FF']);
    pnn_makePerson(['nama' => 'Azifah Mayyasah Al-Banie']);
    pnn_makePerson(['nama' => "Zivana Zayyana Naf'An"]);
    pnn_makePerson(['nama' => 'Febria Putri Nuraini.R']);
    pnn_makePerson(['nama' => "Ummu Fathinmah Rif'At Khanifah"]);

    $this->artisan('person:normalize-names', ['--apply' => true])
        ->expectsOutputToContain('names needing change: 0')
        ->expectsOutputToContain('records to update: 0')
        ->expectsOutputToContain('updated: 0')
        ->assertExitCode(0);

    $this->assertDatabaseHas('people', ['id' => 1, 'nama' => 'Ahmad Agung FF']);
    $this->assertDatabaseHas('people', ['id' => 2, 'nama' => 'Azifah Mayyasah Al-Banie']);
    $this->assertDatabaseHas('people', ['id' => 3, 'nama' => "Zivana Zayyana Naf'An"]);
    $this->assertDatabaseHas('people', ['id' => 4, 'nama' => 'Febria Putri Nuraini.R']);
    $this->assertDatabaseHas('people', ['id' => 5, 'nama' => "Ummu Fathinmah Rif'At Khanifah"]);
});

// ---------------------------------------------------------------------------
// Apply mode
// ---------------------------------------------------------------------------

test('apply normalizes all caps and lowercase names', function () {
    pnn_makePerson(['nama' => 'REFIANTITO']);
    pnn_makePerson(['nama' => 'yudhistira ahmad']);
    pnn_makePerson(['nama' => 'Rizka Aulyia kharisma hasyim']);

    $this->artisan('person:normalize-names', ['--apply' => true])
        ->expectsOutputToContain('updated: 3')
        ->assertExitCode(0);

    $this->assertDatabaseHas('people', ['id' => 1, 'nama' => 'Refiantito']);
    $this->assertDatabaseHas('people', ['id' => 2, 'nama' => 'Yudhistira Ahmad']);
    $this->assertDatabaseHas('people', ['id' => 3, 'nama' => 'Rizka Aulyia Kharisma Hasyim']);
});

test('apply collapses excessive whitespace', function () {
    pnn_makePerson(['nama' => '  MUHAMMAD   ALI  ']);

    $this->artisan('person:normalize-names', ['--apply' => true])->assertExitCode(0);

    $this->assertDatabaseHas('people', ['id' => 1, 'nama' => 'Muhammad Ali']);
});

test('apply keeps already proper case names untouched', function () {
    pnn_makePerson(['nama' => 'Refiantito']);
    pnn_makePerson(['nama' => 'Binti Chusna']);

    $this->artisan('person:normalize-names', ['--apply' => true])
        ->expectsOutputToContain('updated: 0')
        ->assertExitCode(0);

    $this->assertDatabaseHas('people', ['id' => 1, 'nama' => 'Refiantito']);
    $this->assertDatabaseHas('people', ['id' => 2, 'nama' => 'Binti Chusna']);
});

test('apply does not change Person count', function () {
    pnn_makePerson(['nama' => 'REFIANTITO']);
    pnn_makePerson(['nama' => 'vivi nurhaliza audia']);

    $before = Person::count();

    $this->artisan('person:normalize-names', ['--apply' => true])->assertExitCode(0);

    expect(Person::count())->toBe($before);
});

test('apply changes only the nama field', function () {
    $desa = pnn_makeDesa();
    $person = pnn_makePerson([
        'nama' => 'REFIANTITO',
        'jenis_kelamin' => 'L',
        'desa_id' => $desa->id,
        'kelompok_id' => null,
        'tanggal_lahir' => '1999-09-16',
    ]);

    $this->artisan('person:normalize-names', ['--apply' => true])->assertExitCode(0);

    $fresh = $person->fresh();
    expect($fresh->nama)->toBe('Refiantito')
        ->and($fresh->jenis_kelamin)->toBe('L')
        ->and($fresh->desa_id)->toBe($desa->id)
        ->and($fresh->tanggal_lahir?->format('Y-m-d'))->toBe('1999-09-16');
});

test('apply does not touch participation identity fields', function () {
    $event = Event::create(['name' => 'Test Event', 'slug' => 'test-event', 'status' => 'active']);
    $person = pnn_makePerson(['nama' => 'REFIANTITO']);
    $participation = pnn_makeParticipation($person, $event->id);

    $this->artisan('person:normalize-names', ['--apply' => true])->assertExitCode(0);

    $fresh = $participation->fresh();
    expect($fresh->person_id)->toBe($person->id)
        ->and($fresh->participant_number)->toBe('P-'.$person->id)
        ->and($fresh->attendance_code)->toBe('AC-'.$person->id)
        ->and($fresh->event_id)->toBe($event->id);

    $this->assertDatabaseHas('people', ['id' => $person->id, 'nama' => 'Refiantito']);
});

test('apply is idempotent', function () {
    pnn_makePerson(['nama' => 'REFIANTITO']);

    $this->artisan('person:normalize-names', ['--apply' => true])->assertExitCode(0);
    $this->artisan('person:normalize-names', ['--apply' => true])
        ->expectsOutputToContain('updated: 0')
        ->assertExitCode(0);

    $this->assertDatabaseHas('people', ['id' => 1, 'nama' => 'Refiantito']);
});

// ---------------------------------------------------------------------------
// Collision safety
// ---------------------------------------------------------------------------

test('apply reports case-variant collision and skips the colliding rows', function () {
    $desa = pnn_makeDesa();
    $first = pnn_makePerson([
        'nama' => 'MUHAMMAD ALI',
        'desa_id' => $desa->id,
        'tanggal_lahir' => '2000-01-15',
    ]);
    $second = pnn_makePerson([
        'nama' => 'muhammad ali',
        'desa_id' => $desa->id,
        'tanggal_lahir' => '2000-01-15',
    ]);

    $this->artisan('person:normalize-names', ['--apply' => true])
        ->expectsOutputToContain('collision groups (identity: nama + desa_id + tanggal_lahir): 1')
        ->expectsOutputToContain('skipped (collision): 2')
        ->expectsOutputToContain('#'.$first->id)
        ->expectsOutputToContain('#'.$second->id)
        ->assertExitCode(0);

    $this->assertDatabaseHas('people', ['id' => $first->id, 'nama' => 'MUHAMMAD ALI']);
    $this->assertDatabaseHas('people', ['id' => $second->id, 'nama' => 'muhammad ali']);
});

test('case-variant names with different desa are not a collision', function () {
    $desaA = pnn_makeDesa(['desa_asal' => 'Desa A']);
    $desaB = pnn_makeDesa(['desa_asal' => 'Desa B']);
    pnn_makePerson(['nama' => 'MUHAMMAD ALI', 'desa_id' => $desaA->id, 'tanggal_lahir' => '2000-01-15']);
    pnn_makePerson(['nama' => 'muhammad ali', 'desa_id' => $desaB->id, 'tanggal_lahir' => '2000-01-15']);

    $this->artisan('person:normalize-names', ['--apply' => true])
        ->expectsOutputToContain('collision groups (identity: nama + desa_id + tanggal_lahir): 0')
        ->expectsOutputToContain('updated: 2')
        ->assertExitCode(0);
});

test('no new duplicate identities after apply', function () {
    pnn_makePerson(['nama' => 'Budi Santoso']);
    pnn_makePerson(['nama' => 'Siti Aminah']);

    $this->artisan('person:normalize-names', ['--apply' => true])->assertExitCode(0);

    $counts = \Illuminate\Support\Facades\DB::table('people')
        ->selectRaw('nama, COUNT(*) as cnt')
        ->groupBy('nama')
        ->get();

    expect($counts)->toHaveCount(2);

    foreach ($counts as $row) {
        expect((int) $row->cnt)->toBe(1);
    }
});
