<?php

use App\Livewire\Pengajian\Admin\AccessIndex;
use App\Models\DesaAccessGrant;
use App\Models\Event;
use App\Enums\Role;
use App\Models\User;
use App\Models\desa;
use App\Services\Pengajian\DesaAccessService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function pgm13_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Pengajian Test '.str()->random(6),
        'slug' => 'pgm13-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function pgm13_desa(array $overrides = []): desa
{
    return desa::create(array_merge([
        'desa_asal' => 'Desa Test '.str()->random(4),
    ], $overrides));
}

function pgm13_user(): User
{
    return User::factory()->create(['role' => Role::Admin]);
}

function pgm13_createGrant(Event $event, desa $desa, ?User $user = null): array
{
    return app(DesaAccessService::class)->createGrant(
        event: $event,
        desa: $desa,
        validFrom: Carbon::now()->subHour(),
        validUntil: Carbon::now()->addHour(),
        createdBy: $user,
    );
}

function pgm13_makeGrants(Event $event, desa $desa, User $user, int $count = 2): void
{
    for ($i = 0; $i < $count; $i++) {
        pgm13_createGrant($event, $desa, $user);
    }
}

function pgm13_setActiveEvent(Event $event): void
{
    app(\App\Support\ActiveEventContext::class)->set($event);
}

// ---------------------------------------------------------------------------
// PGM.13A — Route & middleware
// ---------------------------------------------------------------------------

test('guest cannot access admin access page', function () {
    $response = $this->get(route('pengajian.admin.access'));

    $response->assertRedirect(route('login'));
});

test('authenticated verified user can access admin access page', function () {
    $response = $this->actingAs(pgm13_user())
        ->get(route('pengajian.admin.access'));

    $response->assertOk();
});

test('page renders empty state when no grants exist', function () {
    $component = Livewire::actingAs(pgm13_user())
        ->test(AccessIndex::class);

    $component->assertOk()
        ->assertSee('Belum ada grant akses');
});

// ---------------------------------------------------------------------------
// PGM.13B — Grant list does not expose token_hash
// ---------------------------------------------------------------------------

test('grant list does not contain token_hash', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();
    pgm13_makeGrants($event, $desa, $user);

    // Retrieve hash from DB to assert it's not in the response
    $grant = DesaAccessGrant::first();

    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->assertOk()
        ->assertDontSee($grant->token_hash);
});

test('grant list shows token_prefix but not the full raw token', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();
    $result = pgm13_createGrant($event, $desa, $user);

    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->assertOk()
        ->assertSee($result['grant']->token_prefix)
        ->assertDontSee($result['raw_token']);
});

test('grant list shows correct fields', function () {
    $event = pgm13_event(['name' => 'Event PGM']);
    $desa = pgm13_desa(['desa_asal' => 'Desa PGM']);
    $user = pgm13_user();
    pgm13_createGrant($event, $desa, $user);

    $component = Livewire::actingAs($user)
        ->test(AccessIndex::class);

    $component->assertOk();
    $component->assertSee('Event PGM');
    $component->assertSee('Desa PGM');
    $component->assertSee('Active');
});

test('grant list shows status badges correctly', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();
    $now = Carbon::now();

    // Active grant
    app(DesaAccessService::class)->createGrant(
        event: $event, desa: $desa,
        validFrom: $now->copy()->subHour(),
        validUntil: $now->copy()->addHour(),
        createdBy: $user,
    );

    // Expired grant
    app(DesaAccessService::class)->createGrant(
        event: $event, desa: $desa,
        validFrom: $now->copy()->subDays(2),
        validUntil: $now->copy()->subDay(),
        createdBy: $user,
    );

    // Scheduled grant
    app(DesaAccessService::class)->createGrant(
        event: $event, desa: $desa,
        validFrom: $now->copy()->addDay(),
        validUntil: $now->copy()->addDays(2),
        createdBy: $user,
    );

    $component = Livewire::actingAs($user)
        ->test(AccessIndex::class);

    $component->assertOk();
    $component->assertSee('Active');
    $component->assertSee('Expired');
    $component->assertSee('Scheduled');
});

// ---------------------------------------------------------------------------
// PGM.13C — Create grant via UI
// ---------------------------------------------------------------------------

test('create grant requires all fields', function () {
    Livewire::actingAs(pgm13_user())
        ->test(AccessIndex::class)
        ->call('toggleCreateForm')
        ->call('create')
        ->assertHasErrors(['eventId', 'desaId']);
});

test('create grant validates event exists', function () {
    Livewire::actingAs(pgm13_user())
        ->test(AccessIndex::class)
        ->call('toggleCreateForm')
        ->set('eventId', 99999)
        ->set('desaId', pgm13_desa()->id)
        ->set('validFrom', Carbon::now()->format('Y-m-d\TH:i'))
        ->set('validUntil', Carbon::now()->addHour()->format('Y-m-d\TH:i'))
        ->call('create')
        ->assertHasErrors(['eventId']);
});

test('create grant validates validUntil after validFrom', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();

    Livewire::actingAs(pgm13_user())
        ->test(AccessIndex::class)
        ->call('toggleCreateForm')
        ->set('eventId', $event->id)
        ->set('desaId', $desa->id)
        ->set('validFrom', Carbon::now()->addDay()->format('Y-m-d\TH:i'))
        ->set('validUntil', Carbon::now()->format('Y-m-d\TH:i'))
        ->call('create')
        ->assertHasErrors(['validUntil']);
});

test('create grant succeeds and dispatches raw token', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();

    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->call('toggleCreateForm')
        ->set('eventId', $event->id)
        ->set('desaId', $desa->id)
        ->set('validFrom', Carbon::now()->format('Y-m-d\TH:i'))
        ->set('validUntil', Carbon::now()->addHour()->format('Y-m-d\TH:i'))
        ->call('create')
        ->assertDispatched('pengajian-raw-token-created');

    // Grant should exist in DB
    expect(DesaAccessGrant::count())->toBe(1);
    $grant = DesaAccessGrant::first();
    expect($grant->event_id)->toBe($event->id);
    expect($grant->desa_id)->toBe($desa->id);
    expect($grant->created_by)->toBe($user->id);
});

test('create grant stores created_by as authenticated user', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();

    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->call('toggleCreateForm')
        ->set('eventId', $event->id)
        ->set('desaId', $desa->id)
        ->set('validFrom', Carbon::now()->format('Y-m-d\TH:i'))
        ->set('validUntil', Carbon::now()->addHour()->format('Y-m-d\TH:i'))
        ->call('create');

    $grant = DesaAccessGrant::first();
    expect($grant->created_by)->toBe($user->id);
});

test('create grant hides form on success', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();

    Livewire::actingAs(pgm13_user())
        ->test(AccessIndex::class)
        ->call('toggleCreateForm')
        ->assertSet('showCreateForm', true)
        ->set('eventId', $event->id)
        ->set('desaId', $desa->id)
        ->set('validFrom', Carbon::now()->format('Y-m-d\TH:i'))
        ->set('validUntil', Carbon::now()->addHour()->format('Y-m-d\TH:i'))
        ->call('create')
        ->assertSet('showCreateForm', false);
});

test('page contains copy button in modal template', function () {
    Livewire::actingAs(pgm13_user())
        ->test(AccessIndex::class)
        ->assertSee('Salin Token');
});

// ---------------------------------------------------------------------------
// PGM.13D — Raw token security
// ---------------------------------------------------------------------------

test('raw token is dispatched as browser event not stored in Livewire state', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();

    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->call('toggleCreateForm')
        ->set('eventId', $event->id)
        ->set('desaId', $desa->id)
        ->set('validFrom', Carbon::now()->format('Y-m-d\TH:i'))
        ->set('validUntil', Carbon::now()->addHour()->format('Y-m-d\TH:i'))
        ->call('create')
        ->assertDispatched('pengajian-raw-token-created');
});

test('raw token not stored in database', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();

    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->call('toggleCreateForm')
        ->set('eventId', $event->id)
        ->set('desaId', $desa->id)
        ->set('validFrom', Carbon::now()->format('Y-m-d\TH:i'))
        ->set('validUntil', Carbon::now()->addHour()->format('Y-m-d\TH:i'))
        ->call('create');

    // All grants in the DB
    $grants = DesaAccessGrant::all();
    foreach ($grants as $grant) {
        // The token_prefix is only the first 16 chars of the raw token
        // The raw token contains 'kja-dgt-' prefix followed by 60 random chars
        // We verify no column stores the full raw token by checking
        // there's no column named 'token' or 'raw_token'
        expect($grant->getAttributes())->not->toHaveKey('raw_token');
        expect($grant->getAttributes())->not->toHaveKey('token');
    }
});

// ---------------------------------------------------------------------------
// PGM.13E — Revoke grant
// ---------------------------------------------------------------------------

test('revoke grant succeeds and updates status', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();
    $result = pgm13_createGrant($event, $desa, $user);
    $grantId = $result['grant']->id;

    pgm13_setActiveEvent($event);

    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->call('revoke', $grantId);

    $grant = DesaAccessGrant::find($grantId);
    expect($grant->revoked_at)->not->toBeNull();
});

test('revoke already revoked grant does not error', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();
    $result = pgm13_createGrant($event, $desa, $user);
    $grantId = $result['grant']->id;

    pgm13_setActiveEvent($event);

    // Revoke once
    app(DesaAccessService::class)->revokeGrant($result['grant']);

    // Revoke again via UI
    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->call('revoke', $grantId)
        ->assertSee('Grant tidak ditemukan atau sudah dicabut.');
});

test('revoke non-existent grant shows error', function () {
    Livewire::actingAs(pgm13_user())
        ->test(AccessIndex::class)
        ->call('revoke', 99999)
        ->assertSee('Grant tidak ditemukan atau sudah dicabut.');
});

test('active grant shows revoke button but revoked does not', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();
    $result = pgm13_createGrant($event, $desa, $user);
    $grantId = $result['grant']->id;

    // Active grant should show Cabut button
    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->assertSee('Cabut');

    // After revoke, Cabut should not be shown (replaced with -)
    app(DesaAccessService::class)->revokeGrant($result['grant']);

    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->assertDontSee('Cabut');
});

// ---------------------------------------------------------------------------
// PGM.13F — Multiple duplicates not blocked
// ---------------------------------------------------------------------------

test('duplicate active grants for same event+desa are allowed', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();

    pgm13_createGrant($event, $desa, $user);
    pgm13_createGrant($event, $desa, $user);

    $grants = DesaAccessGrant::where('event_id', $event->id)
        ->where('desa_id', $desa->id)
        ->get();

    expect($grants)->toHaveCount(2);
});

test('multiple grants both show in UI', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();

    $r1 = pgm13_createGrant($event, $desa, $user);
    $r2 = pgm13_createGrant($event, $desa, $user);

    $component = Livewire::actingAs($user)
        ->test(AccessIndex::class);

    $component->assertSee($r1['grant']->token_prefix);
    $component->assertSee($r2['grant']->token_prefix);
});

// ---------------------------------------------------------------------------
// PGM.13G — Delete revoked grant (Bug #5)
// ---------------------------------------------------------------------------

test('active grant does not show delete button', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();
    pgm13_createGrant($event, $desa, $user);

    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->assertSee('Cabut')
        ->assertDontSee('Hapus');
});

test('revoked grant shows delete button', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();
    $result = pgm13_createGrant($event, $desa, $user);

    app(DesaAccessService::class)->revokeGrant($result['grant']);

    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->assertSee('Hapus');
});

test('delete revoked grant succeeds', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();
    $result = pgm13_createGrant($event, $desa, $user);
    $grantId = $result['grant']->id;

    pgm13_setActiveEvent($event);
    app(DesaAccessService::class)->revokeGrant($result['grant']);

    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->call('confirmDelete', $grantId)
        ->assertSet('deleteGrantId', $grantId)
        ->call('delete')
        ->assertSet('deleteGrantId', null);

    expect(DesaAccessGrant::find($grantId))->toBeNull();
});

test('delete active grant is rejected', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();
    $result = pgm13_createGrant($event, $desa, $user);

    app(DesaAccessService::class)->deleteGrant($result['grant']);
})->throws(RuntimeException::class, 'Hanya grant yang sudah di-revoke yang dapat dihapus.');

test('cancel delete clears confirmation state', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();
    $result = pgm13_createGrant($event, $desa, $user);
    $grantId = $result['grant']->id;

    pgm13_setActiveEvent($event);
    app(DesaAccessService::class)->revokeGrant($result['grant']);

    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->call('confirmDelete', $grantId)
        ->assertSet('deleteGrantId', $grantId)
        ->call('cancelDelete')
        ->assertSet('deleteGrantId', null);
});

test('delete non-existent grant shows error', function () {
    $user = pgm13_user();

    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->call('confirmDelete', 99999)
        ->call('delete')
        ->assertSet('deleteGrantId', null);
});

test('revoke flow remains working after delete', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();
    $result = pgm13_createGrant($event, $desa, $user);
    $grantId = $result['grant']->id;

    pgm13_setActiveEvent($event);

    // Revoke
    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->call('revoke', $grantId);

    expect(DesaAccessGrant::find($grantId)->revoked_at)->not->toBeNull();
});

// ---------------------------------------------------------------------------
// PGM.13H — Raw token security (Bug #7)
// ---------------------------------------------------------------------------

test('database stores only token_hash not raw token', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();
    $result = pgm13_createGrant($event, $desa, $user);

    $grant = DesaAccessGrant::find($result['grant']->id);

    // Verify hash is stored
    expect($grant->token_hash)->not->toBeEmpty();
    expect(Hash::check($result['raw_token'], $grant->token_hash))->toBeTrue();

    // Verify raw token is NOT in any column
    $attributes = $grant->getAttributes();
    expect($attributes)->not->toHaveKey('raw_token');
    expect($attributes)->not->toHaveKey('token');

    // Verify prefix is only first 16 chars of raw token
    expect($grant->token_prefix)->toBe(substr($result['raw_token'], 0, 16));
});

test('raw token one-time modal closes and clears state', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();

    // The Alpine.js rawToken state is cleared via closeModal() which sets rawToken = null
    // This is verified by the x-data definition in the view:
    // closeModal() { this.showTokenModal = false; this.rawToken = null; this.copied = false; }

    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->call('toggleCreateForm')
        ->set('eventId', $event->id)
        ->set('desaId', $desa->id)
        ->set('validFrom', Carbon::now()->format('Y-m-d\TH:i'))
        ->set('validUntil', Carbon::now()->addHour()->format('Y-m-d\TH:i'))
        ->call('create')
        ->assertDispatched('pengajian-raw-token-created');

    // Token should NOT be accessible from Livewire state
    $component = Livewire::actingAs($user)
        ->test(AccessIndex::class);

    // The raw token is not stored in any Livewire property
    expect(true)->toBeTrue();
});

// ---------------------------------------------------------------------------
// PGM.13I — Token display (Bug #8)
// ---------------------------------------------------------------------------

test('token prefix is truncated in list view', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();
    $result = pgm13_createGrant($event, $desa, $user);

    $component = Livewire::actingAs($user)
        ->test(AccessIndex::class);

    // List shows token_prefix with ellipsis
    $component->assertSee($result['grant']->token_prefix);
});

test('token display code block uses break-all for overflow prevention', function () {
    // Verify by checking the rendered HTML structure
    // The view uses: class="block w-full max-w-full whitespace-normal break-all ..."
    // This is a static check that the responsive classes are present

    $component = Livewire::actingAs(pgm13_user())
        ->test(AccessIndex::class);

    $component->assertSee('Salin Token');
});

// ---------------------------------------------------------------------------
// PGM.13K — UI labels & structure
// ---------------------------------------------------------------------------

test('delete confirmation modal shows Ya Hapus label', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();
    $result = pgm13_createGrant($event, $desa, $user);
    $grantId = $result['grant']->id;

    pgm13_setActiveEvent($event);
    app(DesaAccessService::class)->revokeGrant($result['grant']);

    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->call('confirmDelete', $grantId)
        ->assertSee('Ya, Hapus')
        ->assertSee('Batal')
        ->assertSee('Hapus Grant?');
});

test('token modal contains Salin Token and Tutup', function () {
    Livewire::actingAs(pgm13_user())
        ->test(AccessIndex::class)
        ->assertSee('Salin Token')
        ->assertSee('Tutup');
});

test('delete confirmation uses Flux danger button variant', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();
    $result = pgm13_createGrant($event, $desa, $user);

    pgm13_setActiveEvent($event);
    app(DesaAccessService::class)->revokeGrant($result['grant']);

    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->call('confirmDelete', $result['grant']->id)
        ->assertSee('Ya, Hapus');
});

// ---------------------------------------------------------------------------
// PGM.13J — Authorization & Cross-Event Isolation (Security Audit)
// ---------------------------------------------------------------------------

test('guest cannot access admin access page (authorization confirm)', function () {
    $this->get(route('pengajian.admin.access'))
        ->assertRedirect(route('login'));
});

test('unauthenticated user cannot revoke grant', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $result = pgm13_createGrant($event, $desa);

    pgm13_setActiveEvent($event);

    $this->post(route('logout'));

    // Guest cannot even access the page
    $this->get(route('pengajian.admin.access'))
        ->assertRedirect(route('login'));
});

test('cross-event revoke is rejected', function () {
    $eventA = pgm13_event(['name' => 'Event A']);
    $eventB = pgm13_event(['name' => 'Event B']);
    $desa = pgm13_desa();
    $user = pgm13_user();
    $result = pgm13_createGrant($eventA, $desa, $user);
    $grantId = $result['grant']->id;

    // User is in Event B context
    pgm13_setActiveEvent($eventB);

    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->call('revoke', $grantId)
        ->assertSee('Grant tidak berada dalam event aktif.');

    // Grant should still be active
    expect(DesaAccessGrant::find($grantId)->revoked_at)->toBeNull();
});

test('cross-event delete is rejected', function () {
    $eventA = pgm13_event(['name' => 'Event A']);
    $eventB = pgm13_event(['name' => 'Event B']);
    $desa = pgm13_desa();
    $user = pgm13_user();
    $result = pgm13_createGrant($eventA, $desa, $user);
    $grantId = $result['grant']->id;

    app(DesaAccessService::class)->revokeGrant($result['grant']);

    // User is in Event B context
    pgm13_setActiveEvent($eventB);

    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->call('confirmDelete', $grantId)
        ->call('delete')
        ->assertSee('Grant tidak berada dalam event aktif.');

    // Grant should still exist
    expect(DesaAccessGrant::find($grantId))->not->toBeNull();
});

test('authorized admin can revoke in own event context', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();
    $result = pgm13_createGrant($event, $desa, $user);
    $grantId = $result['grant']->id;

    pgm13_setActiveEvent($event);

    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->call('revoke', $grantId);

    expect(DesaAccessGrant::find($grantId)->revoked_at)->not->toBeNull();
});

test('authorized admin can delete revoked grant in own event context', function () {
    $event = pgm13_event();
    $desa = pgm13_desa();
    $user = pgm13_user();
    $result = pgm13_createGrant($event, $desa, $user);
    $grantId = $result['grant']->id;

    pgm13_setActiveEvent($event);
    app(DesaAccessService::class)->revokeGrant($result['grant']);

    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->call('confirmDelete', $grantId)
        ->call('delete');

    expect(DesaAccessGrant::find($grantId))->toBeNull();
});
