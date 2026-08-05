<?php

use App\Models\desa;
use App\Models\DesaAccessGrant;
use App\Models\Event;
use Carbon\Carbon;

function pgm10_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'PGM10 Test Event',
        'slug' => 'pgm10-test-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function pgm10_desa(array $overrides = []): desa
{
    return desa::create(array_merge([
        'desa_asal' => 'PGM10 Desa '.str()->random(4),
    ], $overrides));
}

test('command requires existing event', function () {
    $this->artisan('pengajian:create-desa-grant')
        ->expectsOutputToContain('No events found')
        ->assertExitCode(1);
});

test('command with missing desa option fails', function () {
    pgm10_event();

    $this->artisan('pengajian:create-desa-grant')
        ->expectsOutputToContain('Event')
        ->assertExitCode(1);
});

test('command with valid options and confirmation creates grant', function () {
    $event = pgm10_event();
    $desa = pgm10_desa();
    $validUntil = Carbon::now()->addDays(3)->format('Y-m-d H:i:s');

    $this->artisan('pengajian:create-desa-grant', [
        '--event' => (string) $event->id,
        '--desa' => (string) $desa->id,
        '--valid-until' => $validUntil,
    ])
        ->expectsConfirmation('Create this access grant?', 'yes')
        ->expectsOutputToContain('SAVE THIS TOKEN')
        ->assertExitCode(0);

    expect(DesaAccessGrant::count())->toBe(1);
    $grant = DesaAccessGrant::first();
    expect($grant->event_id)->toBe($event->id);
    expect($grant->desa_id)->toBe($desa->id);
});

test('command validates valid-until after valid-from', function () {
    $event = pgm10_event();
    $desa = pgm10_desa();
    $past = Carbon::now()->subDay()->format('Y-m-d H:i:s');

    $this->artisan('pengajian:create-desa-grant', [
        '--event' => (string) $event->id,
        '--desa' => (string) $desa->id,
        '--valid-until' => $past,
    ])
        ->expectsOutputToContain('valid-until must be after')
        ->assertExitCode(1);
});

test('command rejects non-existent event', function () {
    $this->artisan('pengajian:create-desa-grant', [
        '--event' => '99999',
        '--desa' => '1',
        '--valid-until' => Carbon::now()->addDay()->format('Y-m-d H:i:s'),
    ])
        ->expectsOutputToContain('Event not found')
        ->assertExitCode(1);
});

test('command rejects non-existent desa', function () {
    $event = pgm10_event();

    $this->artisan('pengajian:create-desa-grant', [
        '--event' => (string) $event->id,
        '--desa' => '99999',
        '--valid-until' => Carbon::now()->addDay()->format('Y-m-d H:i:s'),
    ])
        ->expectsOutputToContain('Desa not found')
        ->assertExitCode(1);
});

test('command does not persist raw token', function () {
    $event = pgm10_event();
    $desa = pgm10_desa();
    $validUntil = Carbon::now()->addDays(3)->format('Y-m-d H:i:s');

    $this->artisan('pengajian:create-desa-grant', [
        '--event' => (string) $event->id,
        '--desa' => (string) $desa->id,
        '--valid-until' => $validUntil,
    ])
        ->expectsConfirmation('Create this access grant?', 'yes')
        ->assertExitCode(0);

    $grant = DesaAccessGrant::first();
    expect($grant->token_hash)->not->toBeEmpty();
    expect($grant->nonce)->not->toBeEmpty();
});

test('command cancels gracefully', function () {
    $event = pgm10_event();
    $desa = pgm10_desa();
    $validUntil = Carbon::now()->addDays(3)->format('Y-m-d H:i:s');

    $this->artisan('pengajian:create-desa-grant', [
        '--event' => (string) $event->id,
        '--desa' => (string) $desa->id,
        '--valid-until' => $validUntil,
    ])
        ->expectsConfirmation('Create this access grant?', 'no')
        ->expectsOutputToContain('Cancelled')
        ->assertExitCode(0);

    expect(DesaAccessGrant::count())->toBe(0);
});
