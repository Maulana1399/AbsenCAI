<?php

use App\Enums\Role;
use App\Livewire\Audit\ActivityLogIndex;
use App\Models\User;
use App\Services\Audit\ActivityLogService;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => Role::Admin]);

    $this->event = \App\Models\Event::create([
        'name' => 'Activity Log UI Event '.str()->random(6),
        'slug' => 'activity-log-ui-'.str()->random(6),
        'status' => 'active',
    ]);
});

// ---------------------------------------------------------------------------
// authenticated user can access Activity Log
// ---------------------------------------------------------------------------

test('activity log page is accessible by authenticated user', function () {
    $this->actingAs($this->user)
        ->get(route('activity-log.index', ['event' => $this->event]))
        ->assertStatus(200);
});

// ---------------------------------------------------------------------------
// guest cannot access Activity Log
// ---------------------------------------------------------------------------

test('activity log page requires authentication', function () {
    $this->get(route('activity-log.index', ['event' => $this->event]))->assertRedirect('/login');
});

// ---------------------------------------------------------------------------
// newest logs appear first
// ---------------------------------------------------------------------------

test('activity log displays newest logs first', function () {
    $this->actingAs($this->user);

    app(ActivityLogService::class)->log('first', 'test', 'Old log');
    $this->travel(1)->second();
    app(ActivityLogService::class)->log('second', 'test', 'New log');

    Livewire::test(ActivityLogIndex::class)
        ->assertSeeInOrder(['New log', 'Old log']);
});

// ---------------------------------------------------------------------------
// search works
// ---------------------------------------------------------------------------

test('activity log search filters by description', function () {
    $this->actingAs($this->user);

    app(ActivityLogService::class)->log('test', 'module_a', 'Unique description foo');
    app(ActivityLogService::class)->log('test', 'module_b', 'Another bar description');

    Livewire::test(ActivityLogIndex::class)
        ->set('search', 'foo')
        ->assertSee('Unique description foo')
        ->assertDontSee('Another bar description');
});

// ---------------------------------------------------------------------------
// module filter works
// ---------------------------------------------------------------------------

test('activity log module filter works', function () {
    $this->actingAs($this->user);

    app(ActivityLogService::class)->log('test', 'surat_izin', 'Surat izin log');
    app(ActivityLogService::class)->log('test', 'export', 'Export log');

    Livewire::test(ActivityLogIndex::class)
        ->set('filterModule', 'surat_izin')
        ->assertSee('Surat izin log')
        ->assertDontSee('Export log');
});

// ---------------------------------------------------------------------------
// action filter works
// ---------------------------------------------------------------------------

test('activity log action filter works', function () {
    $this->actingAs($this->user);

    app(ActivityLogService::class)->log('created', 'surat_izin', 'Created log');
    app(ActivityLogService::class)->log('approved', 'surat_izin', 'Approved log');

    Livewire::test(ActivityLogIndex::class)
        ->set('filterAction', 'created')
        ->assertSee('Created log')
        ->assertDontSee('Approved log');
});

// ---------------------------------------------------------------------------
// page is read-only
// ---------------------------------------------------------------------------

test('activity log page is read only no edit or delete actions', function () {
    $this->actingAs($this->user);

    app(ActivityLogService::class)->log('test', 'surat_izin', 'Read-only test');

    Livewire::test(ActivityLogIndex::class)
        ->assertDontSee('Edit')
        ->assertDontSee('Hapus')
        ->assertDontSee('Delete');
});
