<?php

use App\Models\Event;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Enums\Role;
use App\Models\peserta;
use App\Models\User;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function qrIsolation_makeEvent(string $suffix): Event
{
    return Event::create([
        'name' => 'Event '.$suffix,
        'slug' => 'event-'.$suffix.'-'.str()->random(6),
        'status' => 'active',
    ]);
}

function qrIsolation_createMappedPeserta(Event $event, string $name, int $nip, string $attendanceCode): peserta
{
    $peserta = peserta::create([
        'nama' => $name,
        'nip' => $nip,
        'attendance_code' => $attendanceCode,
        'jenis_kelamin' => 'Laki - Laki',
    ]);

    $person = Person::create([
        'nama' => $name,
        'nip' => $nip,
        'jenis_kelamin' => 'L',
    ]);

    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'KL'.str_pad((string) $nip, 3, '0', STR_PAD_LEFT),
        'attendance_code' => $attendanceCode,
        'jenis_peserta' => 'Wajib',
    ]);

    LegacyPesertaMapping::create([
        'peserta_id' => $peserta->id,
        'person_id' => $person->id,
    ]);
    LegacyParticipationMapping::create([
        'peserta_id' => $peserta->id,
        'person_id' => $person->id,
        'participation_id' => $participation->id,
        'event_id' => $event->id,
    ]);

    $peserta->setAttribute('qrIsolationParticipationId', $participation->id);

    return $peserta;
}

// ---------------------------------------------------------------------------
// No-context — all routes must fail closed
// ---------------------------------------------------------------------------

test('selected print returns 403 when no active event context', function () {
    $user = User::factory()->create(['role' => Role::Sekretariat]);
    $this->actingAs($user);

    $event = qrIsolation_makeEvent('A');
    $peserta = qrIsolation_createMappedPeserta($event, 'No Context', 7001, 'KJA-NOCTX1');

    $this->get(route('qr-label.print.selected', ['event' => $event, 'participant' => $peserta->id]))
        ->assertStatus(403);
});

test('filtered print returns 403 when no active event context', function () {
    $user = User::factory()->create(['role' => Role::Sekretariat]);
    $this->actingAs($user);

    $event = qrIsolation_makeEvent('A');
    qrIsolation_createMappedPeserta($event, 'No Context', 7002, 'KJA-NOCTX2');

    $this->get(route('qr-label.print.filtered', ['event' => $event]))
        ->assertStatus(403);
});

test('a4 print returns 403 when no active event context', function () {
    $user = User::factory()->create(['role' => Role::Sekretariat]);
    $this->actingAs($user);

    $event = qrIsolation_makeEvent('A');
    qrIsolation_createMappedPeserta($event, 'No Context', 7003, 'KJA-NOCTX3');

    $this->get(route('qr-label.print.a4', ['event' => $event]))
        ->assertStatus(403);
});

// ---------------------------------------------------------------------------
// Cross-event selected print isolation
// ---------------------------------------------------------------------------

test('selected print rejects participant from another event', function () {
    $user = User::factory()->create(['role' => Role::Sekretariat]);
    $this->actingAs($user);

    $eventA = qrIsolation_makeEvent('A');
    $eventB = qrIsolation_makeEvent('B');
    grantEventRoleToUser($user, $eventA, 'sekretariat');
    grantEventRoleToUser($user, $eventB, 'sekretariat');

    $pesertaA = qrIsolation_createMappedPeserta($eventA, 'Event A Person', 8001, 'KJA-CROSS-A');
    $pesertaB = qrIsolation_createMappedPeserta($eventB, 'Event B Person', 8002, 'KJA-CROSS-B');

    app(ActiveEventContext::class)->set($eventA);

    $this->get(route('qr-label.print.selected', ['event' => $eventA, 'participant' => $pesertaA->qrIsolationParticipationId]))->assertOk();
    $this->get(route('qr-label.print.selected', ['event' => $eventA, 'participant' => $pesertaB->qrIsolationParticipationId]))->assertNotFound();

    app(ActiveEventContext::class)->set($eventB);

    $this->get(route('qr-label.print.selected', ['event' => $eventB, 'participant' => $pesertaB->qrIsolationParticipationId]))->assertOk();
    $this->get(route('qr-label.print.selected', ['event' => $eventB, 'participant' => $pesertaA->qrIsolationParticipationId]))->assertNotFound();
});

// ---------------------------------------------------------------------------
// Cross-event filtered print isolation
// ---------------------------------------------------------------------------

test('filtered print only includes participants from active event', function () {
    $user = User::factory()->create(['role' => Role::Sekretariat]);
    $this->actingAs($user);

    $eventA = qrIsolation_makeEvent('A');
    $eventB = qrIsolation_makeEvent('B');
    grantEventRoleToUser($user, $eventA, 'sekretariat');
    grantEventRoleToUser($user, $eventB, 'sekretariat');

    $pesertaA = qrIsolation_createMappedPeserta($eventA, 'Event A Person', 8003, 'KJA-CROSS-A2');
    qrIsolation_createMappedPeserta($eventB, 'Event B Person', 8004, 'KJA-CROSS-B2');

    app(ActiveEventContext::class)->set($eventA);
    $this->get(route('qr-label.print.filtered', ['event' => $eventA]))
        ->assertOk()
        ->assertSee($pesertaA->nama);

    app(ActiveEventContext::class)->set($eventB);
    $this->get(route('qr-label.print.filtered', ['event' => $eventB]))
        ->assertOk()
        ->assertDontSee($pesertaA->nama);
});

// ---------------------------------------------------------------------------
// Cross-event A4 print isolation
// ---------------------------------------------------------------------------

test('a4 print only includes participants from active event', function () {
    $user = User::factory()->create(['role' => Role::Sekretariat]);
    $this->actingAs($user);

    $eventA = qrIsolation_makeEvent('A');
    $eventB = qrIsolation_makeEvent('B');
    grantEventRoleToUser($user, $eventA, 'sekretariat');
    grantEventRoleToUser($user, $eventB, 'sekretariat');

    $pesertaA = qrIsolation_createMappedPeserta($eventA, 'Event A Person', 8005, 'KJA-CROSS-A3');
    qrIsolation_createMappedPeserta($eventB, 'Event B Person', 8006, 'KJA-CROSS-B3');

    app(ActiveEventContext::class)->set($eventA);
    $this->get(route('qr-label.print.a4', ['event' => $eventA]))
        ->assertOk()
        ->assertSee($pesertaA->nama);

    app(ActiveEventContext::class)->set($eventB);
    $this->get(route('qr-label.print.a4', ['event' => $eventB]))
        ->assertOk()
        ->assertDontSee($pesertaA->nama);
});
