<?php

use App\Enums\Role;
use App\Models\desa;
use App\Models\Event;
use App\Models\kelompok;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\regu;
use App\Models\User;
use App\Support\ActiveEventContext;
use Illuminate\Http\UploadedFile;

/**
 * PARITY TEST — Peserta (final legacy migration).
 * The legacy Maatwebsite import (PesertaImport::model → RegistrationService)
 * produced a full canonical record set per row; the new framework path must
 * produce the same DB writes through the POST route.
 */
function par_event(): Event
{
    return Event::create(['name' => 'Par Peserta', 'slug' => 'par-peserta-'.str()->random(5), 'status' => 'active']);
}

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => Role::SuperAdmin]));
    $this->event = par_event();
    app(ActiveEventContext::class)->set($this->event);
    $this->desa = desa::create(['desa_asal' => 'Par Desa']);
    $this->kelompok = kelompok::create(['kelompok_asal' => 'Par Kelompok', 'desa_id' => $this->desa->id]);
    regu::create(['regu' => 'Par Regu Laki', 'jenis_kelamin' => 'Laki - Laki']);
});

test('parity — POST import peserta creates full canonical record set', function () {
    $this->post(route('import.peserta'), [
        'file' => UploadedFile::fake()->createWithContent('peserta.csv', "nama,jenis_kelamin,kelompok,desa\nPeserta Par,Laki - Laki,Par Kelompok,Par Desa\n"),
    ])->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Data peserta berhasil diimpor.');

    expect(Person::count())->toBe(1)
        ->and(peserta::count())->toBe(1)
        ->and(Participation::where('event_id', $this->event->id)->count())->toBe(1)
        ->and(LegacyPesertaMapping::count())->toBe(1)
        ->and(LegacyParticipationMapping::count())->toBe(1);

    $participation = Participation::first();
    expect($participation->person->nama)->toBe('Peserta Par')
        ->and($participation->person->desa_id)->toBe($this->desa->id)
        ->and($participation->person->kelompok_id)->toBe($this->kelompok->id)
        ->and($participation->regu_id)->not->toBeNull();
});

test('parity — re-importing same person to same event does not duplicate', function () {
    $csv = UploadedFile::fake()->createWithContent('peserta.csv', "nama,jenis_kelamin,kelompok,desa\nPeserta Par,Laki - Laki,Par Kelompok,Par Desa\n");

    $this->post(route('import.peserta'), ['file' => $csv])->assertSessionHasNoErrors();
    $this->post(route('import.peserta'), ['file' => $csv])->assertSessionHasNoErrors();

    // Duplicate membership guard still holds (no duplicate person/participation).
    expect(Person::count())->toBe(1)
        ->and(Participation::where('event_id', $this->event->id)->count())->toBe(1)
        ->and(peserta::count())->toBe(1);
});

test('parity — same person can join a second event', function () {
    $eventB = par_event();
    $csv = UploadedFile::fake()->createWithContent('peserta.csv', "nama,jenis_kelamin,kelompok,desa\nPeserta Par,Laki - Laki,Par Kelompok,Par Desa\n");

    $this->post(route('import.peserta'), ['file' => $csv])->assertSessionHasNoErrors();
    app(ActiveEventContext::class)->set($eventB);
    $this->post(route('import.peserta'), ['file' => $csv])->assertSessionHasNoErrors();

    expect(Person::count())->toBe(1)
        ->and(Participation::where('event_id', $this->event->id)->count())->toBe(1)
        ->and(Participation::where('event_id', $eventB->id)->count())->toBe(1)
        ->and(peserta::count())->toBe(1);
});
