<?php

use App\Livewire\Database\Peserta\EditPeserta;
use App\Livewire\Registrasi\Ulang;
use App\Models\Event;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\regu;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function pe_event(string $suffix): Event
{
    return Event::create([
        'name' => 'PE Event ' . $suffix,
        'slug' => 'pe-' . $suffix . '-' . str()->random(6),
        'status' => 'active',
    ]);
}

function pe_fixture(): array
{
    $desa = desa::create(['desa_asal' => 'PE Desa']);
    $kelompok = kelompok::create(['kelompok_asal' => 'PE Kelompok', 'desa_id' => $desa->id]);
    $regu = regu::create(['regu' => 'PE Regu', 'jenis_kelamin' => 'Laki - Laki']);
    $eventA = pe_event('a');
    $eventB = pe_event('b');
    $person = Person::create([
        'nama' => 'Participant Alpha',
        'nip' => 90001,
        'jenis_kelamin' => 'L',
        'desa_id' => $desa->id,
        'kelompok_id' => $kelompok->id,
    ]);
    $legacy = peserta::create([
        'nama' => 'Participant Alpha',
        'nip' => 90001,
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => 'Wajib',
        'desa_id' => $desa->id,
        'kelompok_id' => $kelompok->id,
        'regu_id' => $regu->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);
    $participationA = Participation::create([
        'person_id' => $person->id,
        'event_id' => $eventA->id,
        'participant_number' => 'KA001',
        'attendance_code' => 'KJA-PEA00001',
        'jenis_peserta' => 'Wajib',
    ]);
    $participationB = Participation::create([
        'person_id' => $person->id,
        'event_id' => $eventB->id,
        'participant_number' => 'KB001',
        'attendance_code' => 'KJA-PEB00001',
        'jenis_peserta' => 'Kiriman',
    ]);

    LegacyPesertaMapping::create([
        'peserta_id' => $legacy->id,
        'person_id' => $person->id,
        'legacy_nip' => $legacy->nip,
        'legacy_participant_number' => $legacy->participant_number,
        'legacy_attendance_code' => $legacy->attendance_code,
        'migrated_at' => now(),
    ]);
    LegacyParticipationMapping::create([
        'peserta_id' => $legacy->id,
        'person_id' => $person->id,
        'participation_id' => $participationA->id,
        'event_id' => $eventA->id,
        'migrated_at' => now(),
    ]);
    LegacyParticipationMapping::create([
        'peserta_id' => $legacy->id,
        'person_id' => $person->id,
        'participation_id' => $participationB->id,
        'event_id' => $eventB->id,
        'migrated_at' => now(),
    ]);

    return compact('desa', 'kelompok', 'regu', 'eventA', 'eventB', 'person', 'legacy', 'participationA', 'participationB');
}

beforeEach(function () {
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'admin']));
});

test('EditPeserta resolves active-event participation and keeps other event untouched', function () {
    extract(pe_fixture());
    app(ActiveEventContext::class)->set($eventA);

    Livewire::test(EditPeserta::class)
        ->call('editPeserta', $legacy->id)
        ->set('nama', 'Participant Alpha A')
        ->set('jenis_peserta', 'Wajib')
        ->set('desa_id', $desa->id)
        ->set('kelompok_id', $kelompok->id)
        ->set('regu_id', $regu->id)
        ->call('update');

    expect($participationA->fresh()->person->nama)->toBe('Participant Alpha A')
        ->and($participationA->fresh()->jenis_peserta)->toBe('Wajib')
        ->and($participationB->fresh()->person->nama)->toBe('Participant Alpha A')
        ->and($participationB->fresh()->jenis_peserta)->toBe('Kiriman');
});

test('EditPeserta rejects Event A context for Event B only participation', function () {
    $eventA = pe_event('a-only');
    $eventB = pe_event('b-only');
    $person = Person::create(['nama' => 'Only B', 'nip' => 90011, 'jenis_kelamin' => 'L', 'desa_id' => null, 'kelompok_id' => null]);
    $legacy = peserta::create(['nama' => 'Only B', 'nip' => 90011, 'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib', 'desa_id' => null, 'kelompok_id' => null, 'regu_id' => null, 'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI]);
    $participationB = Participation::create(['person_id' => $person->id, 'event_id' => $eventB->id, 'participant_number' => 'KB010', 'attendance_code' => 'KJA-ONLYB01', 'jenis_peserta' => 'Wajib']);
    LegacyPesertaMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'legacy_nip' => $legacy->nip, 'legacy_participant_number' => $legacy->participant_number, 'legacy_attendance_code' => $legacy->attendance_code, 'migrated_at' => now()]);
    LegacyParticipationMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'participation_id' => $participationB->id, 'event_id' => $eventB->id, 'migrated_at' => now()]);

    app(ActiveEventContext::class)->set($eventA);

    $component = Livewire::test(EditPeserta::class)->call('editPeserta', $legacy->id);
    expect($component->get('participation_id'))->toBeNull();
});

test('Ulang edits active-event participation only', function () {
    extract(pe_fixture());
    app(ActiveEventContext::class)->set($eventA);

    Livewire::test(Ulang::class)
        ->call('editPeserta', $legacy->id)
        ->set('editNama', 'Participant Alpha Ulang A')
        ->set('editJenisPeserta', 'Person')
        ->set('editDesa', $desa->id)
        ->set('editKelompok', $kelompok->id)
        ->set('editRegu', $regu->id)
        ->call('updatePeserta');

    expect($participationA->fresh()->person->nama)->toBe('Participant Alpha Ulang A')
        ->and($participationA->fresh()->jenis_peserta)->toBe('Person')
        ->and($participationB->fresh()->person->nama)->toBe('Participant Alpha Ulang A')
        ->and($participationB->fresh()->jenis_peserta)->toBe('Kiriman');
});

test('Ulang registrasi ulang uses active-event membership and legacy mirror only', function () {
    extract(pe_fixture());
    app(ActiveEventContext::class)->set($eventA);

    Livewire::test(Ulang::class)
        ->call('registrasiUlang', $legacy->id);

    expect($legacy->fresh()->status_registrasi)->toBe(peserta::STATUS_REGISTRASI_ULANG)
        ->and($participationA->fresh()->jenis_peserta)->toBe('Wajib')
        ->and($participationB->fresh()->jenis_peserta)->toBe('Kiriman');
});

test('Global person identity edits are visible across both events without duplicating records', function () {
    extract(pe_fixture());
    app(ActiveEventContext::class)->set($eventA);

    Livewire::test(EditPeserta::class)
        ->call('editPeserta', $participationA->id)
        ->set('nama', 'Participant Omega')
        ->set('jenis_peserta', 'Kiriman')
        ->set('desa_id', $desa->id)
        ->set('kelompok_id', $kelompok->id)
        ->set('regu_id', $regu->id)
        ->call('update');

    expect(Person::count())->toBe(1)
        ->and(peserta::count())->toBe(1)
        ->and($participationA->fresh()->person->nama)->toBe('Participant Omega')
        ->and($participationB->fresh()->person->nama)->toBe('Participant Omega');
});

test('Existing Person joining Event B through registration follows Case B without duplicating global records', function () {
    $eventA = pe_event('case-b-a');
    $eventB = pe_event('case-b-b');
    $person = Person::create(['nama' => 'Case B Person', 'nip' => 90021, 'jenis_kelamin' => 'L', 'desa_id' => null, 'kelompok_id' => null]);
    $legacy = peserta::create(['nama' => 'Case B Person', 'nip' => 90021, 'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib', 'desa_id' => null, 'kelompok_id' => null, 'regu_id' => null, 'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI]);
    $participationA = Participation::create(['person_id' => $person->id, 'event_id' => $eventA->id, 'participant_number' => 'KA010', 'attendance_code' => 'KJA-CASEB010', 'jenis_peserta' => 'Wajib']);
    LegacyPesertaMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'legacy_nip' => $legacy->nip, 'legacy_participant_number' => $legacy->participant_number, 'legacy_attendance_code' => $legacy->attendance_code, 'migrated_at' => now()]);
    LegacyParticipationMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'participation_id' => $participationA->id, 'event_id' => $eventA->id, 'migrated_at' => now()]);

    app(ActiveEventContext::class)->set($eventB);

    $before = [
        'people' => Person::count(),
        'peserta' => peserta::count(),
        'legacy_peserta' => LegacyPesertaMapping::count(),
        'participations' => Participation::count(),
        'legacy_participations' => LegacyParticipationMapping::count(),
    ];

    $result = app(\App\Services\Registration\RegistrationService::class)->createParticipant([
        'nama' => $person->nama,
        'nip' => $person->nip,
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => 'Wajib',
        'desa_id' => $person->desa_id,
        'kelompok_id' => $person->kelompok_id,
        'regu_id' => null,
        'status_registrasi' => peserta::STATUS_SELF_REGISTER,
    ]);

    expect($result)->toBeInstanceOf(peserta::class)
        ->and(Person::count())->toBe($before['people'])
        ->and(peserta::count())->toBe($before['peserta'])
        ->and(LegacyPesertaMapping::count())->toBe($before['legacy_peserta'])
        ->and(Participation::count())->toBe($before['participations'] + 1)
        ->and(LegacyParticipationMapping::count())->toBe($before['legacy_participations'] + 1);
});
