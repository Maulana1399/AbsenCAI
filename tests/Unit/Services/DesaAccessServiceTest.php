<?php

use App\Models\desa;
use App\Models\DesaAccessGrant;
use App\Models\Event;
use App\Models\User;
use App\Services\Pengajian\DesaAccessService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function pgm_makeEvent(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Pengajian Desa Test',
        'slug' => 'pengajian-desa-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function pgm_makeDesa(array $overrides = []): desa
{
    return desa::create(array_merge([
        'desa_asal' => 'Desa Test '.str()->random(4),
    ], $overrides));
}

function pgm_makeUser(): User
{
    return User::factory()->create();
}

function pgm_service(): DesaAccessService
{
    return app(DesaAccessService::class);
}

// ---------------------------------------------------------------------------
// Grant creation contract
// ---------------------------------------------------------------------------

test('createGrant returns grant model and raw token', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $user = pgm_makeUser();
    $now = Carbon::now();
    $validFrom = $now->copy()->subDay();
    $validUntil = $now->copy()->addDay();

    $result = pgm_service()->createGrant($event, $desa, $validFrom, $validUntil, $user);

    expect($result)->toHaveKeys(['grant', 'raw_token']);
    expect($result['grant'])->toBeInstanceOf(DesaAccessGrant::class);
    expect($result['raw_token'])->toBeString()->not->toBeEmpty();

    expect($result['grant']->event_id)->toBe($event->id);
    expect($result['grant']->desa_id)->toBe($desa->id);
    expect($result['grant']->created_by)->toBe($user->id);
});

test('createGrant stores hash not raw token', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $result = pgm_service()->createGrant(
        $event, $desa, $now->copy()->subDay(), $now->copy()->addDay()
    );

    $dbGrant = DesaAccessGrant::find($result['grant']->id);
    expect($dbGrant->token_hash)->not->toBe($result['raw_token']);
    expect(Hash::check($result['raw_token'], $dbGrant->token_hash))->toBeTrue();
});

test('createGrant sets token_prefix as first 16 chars of raw token', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $result = pgm_service()->createGrant(
        $event, $desa, $now->copy()->subDay(), $now->copy()->addDay()
    );

    expect($result['grant']->token_prefix)->toBe(substr($result['raw_token'], 0, 16));
});

test('createGrant generates unique nonce', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $resultA = pgm_service()->createGrant($event, $desa, $now->copy()->subDay(), $now->copy()->addDay());
    $resultB = pgm_service()->createGrant($event, $desa, $now->copy()->subDay(), $now->copy()->addDay());

    expect($resultA['grant']->nonce)->not->toBe($resultB['grant']->nonce);
});

test('raw token starts with kja-dgt- prefix', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $result = pgm_service()->createGrant(
        $event, $desa, $now->copy()->subDay(), $now->copy()->addDay()
    );

    expect($result['raw_token'])->toStartWith('kja-dgt-');
});

// ---------------------------------------------------------------------------
// Token validation contract
// ---------------------------------------------------------------------------

test('valid token resolves correct grant with event and desa', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $result = pgm_service()->createGrant(
        $event, $desa, $now->copy()->subHour(), $now->copy()->addHour()
    );

    $resolved = pgm_service()->validateToken($result['raw_token'], $event->id);

    expect($resolved)->not->toBeNull();
    expect($resolved->id)->toBe($result['grant']->id);
    expect($resolved->event_id)->toBe($event->id);
    expect($resolved->desa_id)->toBe($desa->id);
});

test('wrong token returns null', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    pgm_service()->createGrant($event, $desa, $now->copy()->subHour(), $now->copy()->addHour());

    $resolved = pgm_service()->validateToken('kja-dgt-wrongtoken1234567890abcdefghij', $event->id);

    expect($resolved)->toBeNull();
});

test('wrong event scope returns null even with valid token', function () {
    $eventA = pgm_makeEvent(['slug' => 'event-a']);
    $eventB = pgm_makeEvent(['slug' => 'event-b']);
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $result = pgm_service()->createGrant(
        $eventA, $desa, $now->copy()->subHour(), $now->copy()->addHour()
    );

    $resolved = pgm_service()->validateToken($result['raw_token'], $eventB->id);

    expect($resolved)->toBeNull();
});

test('desa A token cannot resolve for desa B', function () {
    $event = pgm_makeEvent();
    $desaA = pgm_makeDesa(['desa_asal' => 'Desa A']);
    $desaB = pgm_makeDesa(['desa_asal' => 'Desa B']);
    $now = Carbon::now();

    $result = pgm_service()->createGrant(
        $event, $desaA, $now->copy()->subHour(), $now->copy()->addHour()
    );

    $resolved = pgm_service()->validateToken($result['raw_token'], $event->id);

    expect($resolved)->not->toBeNull();
    expect($resolved->desa_id)->toBe($desaA->id);
    expect($resolved->desa_id)->not->toBe($desaB->id);
});

test('expired token returns null', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $result = pgm_service()->createGrant(
        $event, $desa, $now->copy()->subDays(2), $now->copy()->subDay()
    );

    $resolved = pgm_service()->validateToken($result['raw_token'], $event->id);

    expect($resolved)->toBeNull();
});

test('not yet valid token returns null', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $result = pgm_service()->createGrant(
        $event, $desa, $now->copy()->addDay(), $now->copy()->addDays(2)
    );

    $resolved = pgm_service()->validateToken($result['raw_token'], $event->id);

    expect($resolved)->toBeNull();
});

test('revoked token returns null', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $result = pgm_service()->createGrant(
        $event, $desa, $now->copy()->subHour(), $now->copy()->addHour()
    );

    pgm_service()->revokeGrant($result['grant']);

    $resolved = pgm_service()->validateToken($result['raw_token'], $event->id);

    expect($resolved)->toBeNull();
});

test('revoked grant is persisted with revoked_at', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $result = pgm_service()->createGrant(
        $event, $desa, $now->copy()->subHour(), $now->copy()->addHour()
    );

    pgm_service()->revokeGrant($result['grant']);

    $grant = $result['grant']->fresh();
    expect($grant->revoked_at)->not->toBeNull();
    expect(Carbon::parse($grant->revoked_at)->diffInSeconds(now()))->toBeLessThan(5);
});

test('token with less than 16 chars returns null early', function () {
    $event = pgm_makeEvent();

    $resolved = pgm_service()->validateToken('short', $event->id);

    expect($resolved)->toBeNull();
});

test('multiple grants with same prefix does not cause wrong resolution', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $resultA = pgm_service()->createGrant(
        $event, $desa, $now->copy()->subHour(), $now->copy()->addHour()
    );
    $resultB = pgm_service()->createGrant(
        $event, $desa, $now->copy()->subHour(), $now->copy()->addHour()
    );

    // Force same prefix by manipulating database (mimic collision)
    $samePrefix = $resultA['grant']->token_prefix;
    $resultB['grant']->update(['token_prefix' => $samePrefix]);

    // Resolve token A with prefix matching both
    $resolved = pgm_service()->validateToken($resultA['raw_token'], $event->id);

    expect($resolved)->not->toBeNull();
    expect($resolved->id)->toBe($resultA['grant']->id);
});

// ---------------------------------------------------------------------------
// Nonce contract
// ---------------------------------------------------------------------------

test('resolveNonce returns correct event_id and desa_id', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $result = pgm_service()->createGrant(
        $event, $desa, $now->copy()->subHour(), $now->copy()->addHour()
    );

    $resolved = pgm_service()->resolveNonce($result['grant']->nonce);

    expect($resolved)->not->toBeNull();
    expect($resolved['event_id'])->toBe($event->id);
    expect($resolved['desa_id'])->toBe($desa->id);
});

test('resolveNonce with invalid nonce returns null', function () {
    $resolved = pgm_service()->resolveNonce('nonexistent-nonce-value');

    expect($resolved)->toBeNull();
});

test('resolveNonce with expired nonce returns null', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $result = pgm_service()->createGrant(
        $event, $desa, $now->copy()->subHour(), $now->copy()->addHour(),
        nonceTtlMinutes: -1
    );

    $resolved = pgm_service()->resolveNonce($result['grant']->nonce);

    expect($resolved)->toBeNull();
});

test('resolveNonce with revoked grant returns null', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $result = pgm_service()->createGrant(
        $event, $desa, $now->copy()->subHour(), $now->copy()->addHour()
    );

    pgm_service()->revokeGrant($result['grant']);

    $resolved = pgm_service()->resolveNonce($result['grant']->nonce);

    expect($resolved)->toBeNull();
});

test('resolveNonce with expired grant returns null', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $result = pgm_service()->createGrant(
        $event, $desa, $now->copy()->subDays(2), $now->copy()->subDay()
    );

    $resolved = pgm_service()->resolveNonce($result['grant']->nonce);

    expect($resolved)->toBeNull();
});

test('resolveNonce with not-yet-valid grant returns null', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $result = pgm_service()->createGrant(
        $event, $desa, $now->copy()->addDay(), $now->copy()->addDays(2)
    );

    $resolved = pgm_service()->resolveNonce($result['grant']->nonce);

    expect($resolved)->toBeNull();
});

// ---------------------------------------------------------------------------
// Nonce exchange/rotation contract
// ---------------------------------------------------------------------------

test('exchangeForNonce rotates nonce', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $result = pgm_service()->createGrant(
        $event, $desa, $now->copy()->subHour(), $now->copy()->addHour()
    );

    $oldNonce = $result['grant']->nonce;
    $newNonce = pgm_service()->exchangeForNonce($result['grant']);

    expect($newNonce)->not->toBe($oldNonce);
    expect($result['grant']->fresh()->nonce)->toBe($newNonce);
});

test('old nonce is invalid after rotate', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $result = pgm_service()->createGrant(
        $event, $desa, $now->copy()->subHour(), $now->copy()->addHour()
    );

    $oldNonce = $result['grant']->nonce;
    pgm_service()->exchangeForNonce($result['grant']);

    $resolved = pgm_service()->resolveNonce($oldNonce);
    expect($resolved)->toBeNull();
});

// ---------------------------------------------------------------------------
// Model helper contract
// ---------------------------------------------------------------------------

test('DesaAccessGrant isValid returns correct state', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $result = pgm_service()->createGrant(
        $event, $desa, $now->copy()->subHour(), $now->copy()->addHour()
    );

    expect($result['grant']->isValid())->toBeTrue();
    expect($result['grant']->isExpired())->toBeFalse();
    expect($result['grant']->isRevoked())->toBeFalse();
    expect($result['grant']->isNonceValid())->toBeTrue();

    pgm_service()->revokeGrant($result['grant']);
    $grant = $result['grant']->fresh();

    expect($grant->isValid())->toBeFalse();
    expect($grant->isRevoked())->toBeTrue();
    expect($grant->isNonceValid())->toBeFalse();
});

test('DesaAccessGrant scopeActive returns only valid grants', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $good = pgm_service()->createGrant(
        $event, $desa, $now->copy()->subHour(), $now->copy()->addHour()
    );
    $expired = pgm_service()->createGrant(
        $event, $desa, $now->copy()->subDays(2), $now->copy()->subDay()
    );
    $future = pgm_service()->createGrant(
        $event, $desa, $now->copy()->addDay(), $now->copy()->addDays(2)
    );
    $revoked = pgm_service()->createGrant(
        $event, $desa, $now->copy()->subHour(), $now->copy()->addHour()
    );

    pgm_service()->revokeGrant($revoked['grant']);

    $activeGrants = DesaAccessGrant::active()->get();

    expect($activeGrants->pluck('id'))->toContain($good['grant']->id);
    expect($activeGrants->pluck('id'))->not->toContain($expired['grant']->id);
    expect($activeGrants->pluck('id'))->not->toContain($future['grant']->id);
    expect($activeGrants->pluck('id'))->not->toContain($revoked['grant']->id);
});

// ---------------------------------------------------------------------------
// Schema contract
// ---------------------------------------------------------------------------

test('desa_access_grants table has expected columns', function () {
    $columns = Schema::getColumnListing('desa_access_grants');

    $expected = [
        'id',
        'event_id',
        'desa_id',
        'token_hash',
        'token_prefix',
        'valid_from',
        'valid_until',
        'revoked_at',
        'nonce',
        'nonce_expires_at',
        'created_by',
        'created_at',
        'updated_at',
    ];

    foreach ($expected as $column) {
        expect($columns)->toContain($column);
    }
});

test('desa_access_grants has unique nonce constraint', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    pgm_service()->createGrant($event, $desa, $now->copy()->subDay(), $now->copy()->addDay());

    expect(fn () => DesaAccessGrant::create([
        'event_id' => $event->id,
        'desa_id' => $desa->id,
        'token_hash' => 'somehash',
        'token_prefix' => 'prefix123',
        'valid_from' => $now->copy()->subDay(),
        'valid_until' => $now->copy()->addDay(),
        'nonce' => DesaAccessGrant::first()->nonce,
        'nonce_expires_at' => $now->copy()->addDay(),
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

// ---------------------------------------------------------------------------
// Security
// ---------------------------------------------------------------------------

test('raw token is never stored in database', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $result = pgm_service()->createGrant(
        $event, $desa, $now->copy()->subHour(), $now->copy()->addHour()
    );

    $allColumns = DesaAccessGrant::first()->toArray();
    $allValues = implode('|', array_map('strval', array_values($allColumns)));

    expect($allValues)->not->toContain($result['raw_token']);
});

test('token_prefix reveals only first 16 chars of token', function () {
    $event = pgm_makeEvent();
    $desa = pgm_makeDesa();
    $now = Carbon::now();

    $result = pgm_service()->createGrant(
        $event, $desa, $now->copy()->subHour(), $now->copy()->addHour()
    );

    $prefix = $result['grant']->token_prefix;
    expect(strlen($prefix))->toBe(16);
    expect($prefix)->toBe(substr($result['raw_token'], 0, 16));
    // Prefix alone should not match raw token hash
    expect(Hash::check($prefix, $result['grant']->token_hash))->toBeFalse();
});
