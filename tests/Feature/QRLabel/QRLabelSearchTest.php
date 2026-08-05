<?php

use App\Livewire\QRLabel\Index;
use App\Models\Event;
use App\Models\Participation;
use App\Models\Person;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function qr_search_event(string $suffix): Event
{
    return Event::create([
        'name' => 'QR Search '.$suffix,
        'slug' => 'qr-search-'.$suffix.'-'.str()->random(6),
        'status' => 'active',
    ]);
}

test('qr label search filters active event participations by participant name', function () {
    $eventA = qr_search_event('a');
    $eventB = qr_search_event('b');
    app(ActiveEventContext::class)->set($eventA);

    $personA = Person::create(['nama' => 'Search Lana', 'nip' => 81001, 'jenis_kelamin' => 'L']);
    $personB = Person::create(['nama' => 'Search Other', 'nip' => 81002, 'jenis_kelamin' => 'P']);

    Participation::create(['person_id' => $personA->id, 'event_id' => $eventA->id, 'participant_number' => 'KA001', 'attendance_code' => 'KJA-SEARCH-A', 'jenis_peserta' => 'Wajib']);
    Participation::create(['person_id' => $personB->id, 'event_id' => $eventB->id, 'participant_number' => 'KB001', 'attendance_code' => 'KJA-SEARCH-B', 'jenis_peserta' => 'Wajib']);

    Livewire::test(Index::class)
        ->set('filterKeyword', 'lana')
        ->assertSee('Search Lana')
        ->assertDontSee('Search Other');
});

test('qr label search stays scoped to active event', function () {
    $eventA = qr_search_event('scope-a');
    $eventB = qr_search_event('scope-b');
    app(ActiveEventContext::class)->set($eventA);

    $personA = Person::create(['nama' => 'Scope Lana', 'nip' => 81101, 'jenis_kelamin' => 'L']);
    $personB = Person::create(['nama' => 'Scope Lana', 'nip' => 81102, 'jenis_kelamin' => 'P']);

    $partA = Participation::create(['person_id' => $personA->id, 'event_id' => $eventA->id, 'participant_number' => 'KA002', 'attendance_code' => 'KJA-SCOPE-A', 'jenis_peserta' => 'Wajib']);
    Participation::create(['person_id' => $personB->id, 'event_id' => $eventB->id, 'participant_number' => 'KB002', 'attendance_code' => 'KJA-SCOPE-B', 'jenis_peserta' => 'Wajib']);

    Livewire::test(Index::class)
        ->set('filterKeyword', 'scope lana')
        ->assertSee('KA002')
        ->assertDontSee('KB002');
});
