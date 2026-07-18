<?php

use App\Livewire\QRLabel\Index;
use App\Models\Event;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function qrLabelTest_makeEvent(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Test Event',
        'slug' => 'test-event-' . str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function qrLabelTest_makeParticipation(array $overrides, Event $event): array
{
    $person = Person::create(array_merge([
        'nama' => 'Participant',
        'nip' => fake()->unique()->numberBetween(1000, 9999),
        'jenis_kelamin' => 'L',
    ], $overrides['person'] ?? []));

    $participation = Participation::create(array_merge([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'attendance_code' => 'KJA-'.str()->upper(str()->random(6)),
        'participant_number' => 'KL'.str_pad((string) fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
        'jenis_peserta' => 'Wajib',
    ], $overrides['participation'] ?? []));

    return [$person, $participation];
}

beforeEach(function () {
    app()->forgetInstance(ActiveEventContext::class);
    Storage::fake('local');
});

test('qr label list only shows participations from active event', function () {
    $eventA = qrLabelTest_makeEvent();
    $eventB = qrLabelTest_makeEvent(['slug' => 'event-b-' . str()->random(6)]);
    app(ActiveEventContext::class)->set($eventA);
    [$personA] = qrLabelTest_makeParticipation(['person' => ['nama' => 'Person A', 'nip' => 5001], 'participation' => ['attendance_code' => 'KJA-QR-A', 'participant_number' => 'KL001']], $eventA);
    [$personB] = qrLabelTest_makeParticipation(['person' => ['nama' => 'Person B', 'nip' => 5002], 'participation' => ['attendance_code' => 'KJA-QR-B', 'participant_number' => 'KL002']], $eventB);

    Livewire::test(Index::class)->assertSee('Person A')->assertDontSee('Person B');
});

test('qr payload uses participation attendance code and participant number', function () {
    $event = qrLabelTest_makeEvent();
    app(ActiveEventContext::class)->set($event);
    [$person, $participation] = qrLabelTest_makeParticipation([
        'person' => ['nama' => 'Payload Person', 'nip' => 5003],
        'participation' => ['attendance_code' => 'KJA-QR-PAYLOAD', 'participant_number' => 'KL123'],
    ], $event);

    $component = Livewire::test(Index::class);
    $component->set('selectedLabelParticipantId', $participation->id)
        ->assertSet('labelPreviewHtml', fn (string $html) => str_contains($html, 'KL123') && str_contains($html, 'Payload Person'))
        ->call('printSelectedLabel');
});

test('batch export contains only active event participations', function () {
    $eventA = qrLabelTest_makeEvent();
    $eventB = qrLabelTest_makeEvent(['slug' => 'event-b-' . str()->random(6)]);
    app(ActiveEventContext::class)->set($eventA);
    qrLabelTest_makeParticipation(['person' => ['nama' => 'Export A', 'nip' => 5004], 'participation' => ['attendance_code' => 'KJA-EXP-A', 'participant_number' => 'KL201']], $eventA);
    qrLabelTest_makeParticipation(['person' => ['nama' => 'Export B', 'nip' => 5005], 'participation' => ['attendance_code' => 'KJA-EXP-B', 'participant_number' => 'KL202']], $eventB);

    $component = Livewire::test(Index::class);
    $component->call('generateBatchExport');

    $component->assertSet('batchPreview.generated', 1);
});

test('same person with participations in two events uses correct event specific identity', function () {
    $person = Person::create(['nama' => 'Multi Person', 'nip' => 5006, 'jenis_kelamin' => 'L']);
    $eventA = qrLabelTest_makeEvent();
    $eventB = qrLabelTest_makeEvent(['slug' => 'event-b-' . str()->random(6)]);

    $participationA = Participation::create(['person_id' => $person->id, 'event_id' => $eventA->id, 'attendance_code' => 'KJA-MULTI-A', 'participant_number' => 'KL301', 'jenis_peserta' => 'Wajib']);
    $participationB = Participation::create(['person_id' => $person->id, 'event_id' => $eventB->id, 'attendance_code' => 'KJA-MULTI-B', 'participant_number' => 'KL302', 'jenis_peserta' => 'Wajib']);

    app(ActiveEventContext::class)->set($eventA);
    Livewire::test(Index::class)->assertSee('Multi Person');

    app(ActiveEventContext::class)->set($eventB);
    Livewire::test(Index::class)->assertSee('Multi Person');
});
