<?php

use App\Livewire\Event\CommitteeManagement;
use App\Models\Event;
use App\Models\EventCommitteeAssignment;
use App\Models\EventRole;
use App\Models\Person;
use App\Models\User;
use App\Services\Activity\EventCommitteeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function cmr_event(): Event
{
    return Event::create(['name' => 'CMR Event '.str()->random(6), 'slug' => 'cmr-'.str()->random(6), 'status' => 'active']);
}

function cmr_person(): Person
{
    return Person::create(['nama' => 'CMR Person '.str()->random(6)]);
}

function cmr_role(Event $event): EventRole
{
    return EventRole::create(['event_id' => $event->id, 'name' => 'Ketua', 'code' => 'ketua_event']);
}

function cmr_assign(Event $event, Person $person, EventRole $role): EventCommitteeAssignment
{
    return app(EventCommitteeService::class)->assign([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);
}

// ---------------------------------------------------------------------------
// Single-step delete flow
// ---------------------------------------------------------------------------

test('single-step delete removes assignment in same event', function () {
    $event = cmr_event();
    $person = cmr_person();
    $role = cmr_role($event);
    $assignment = cmr_assign($event, $person, $role);
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user);
    Livewire::test(CommitteeManagement::class)
        ->dispatch('manageCommittee', id: $event->id)
        ->call('delete', $assignment->id);

    expect(EventCommitteeAssignment::find($assignment->id))->toBeNull();
});

test('assignment from another event cannot be deleted', function () {
    $eventA = cmr_event();
    $eventB = cmr_event();
    $person = cmr_person();
    $roleB = cmr_role($eventB);
    $assignmentB = cmr_assign($eventB, $person, $roleB);
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user);
    Livewire::test(CommitteeManagement::class)
        ->dispatch('manageCommittee', id: $eventA->id)
        ->call('delete', $assignmentB->id);

    expect(EventCommitteeAssignment::find($assignmentB->id))->not->toBeNull();
});

test('delete flashes error when assignment not found', function () {
    $event = cmr_event();
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user);
    Livewire::test(CommitteeManagement::class)
        ->dispatch('manageCommittee', id: $event->id)
        ->call('delete', 99999)
        ->assertSee('Penugasan tidak ditemukan.');
});

// ---------------------------------------------------------------------------
// Authorization tetap berjalan
// ---------------------------------------------------------------------------

test('unauthorized role cannot delete assignment', function () {
    $event = cmr_event();
    $person = cmr_person();
    $role = cmr_role($event);
    $assignment = cmr_assign($event, $person, $role);
    $user = User::factory()->create(['role' => null]);

    $this->actingAs($user);

    expect(Gate::forUser($user)->denies('manage-events'))->toBeTrue();

    Livewire::test(CommitteeManagement::class)
        ->dispatch('manageCommittee', id: $event->id)
        ->call('delete', $assignment->id)
        ->assertForbidden();

    expect(EventCommitteeAssignment::find($assignment->id))->not->toBeNull();
});

// ---------------------------------------------------------------------------
// confirmDelete tidak lagi ada di flow (regresi flow lama)
// ---------------------------------------------------------------------------

test('delete flow no longer relies on confirmDelete staging', function () {
    $event = cmr_event();
    $person = cmr_person();
    $role = cmr_role($event);
    $assignment = cmr_assign($event, $person, $role);
    $user = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user);
    $component = Livewire::test(CommitteeManagement::class)
        ->dispatch('manageCommittee', id: $event->id);

    // Property deleteAssignmentId tidak boleh ada lagi
    expect(property_exists(CommitteeManagement::class, 'deleteAssignmentId'))->toBeFalse();
    expect(method_exists(CommitteeManagement::class, 'confirmDelete'))->toBeFalse();

    // Single step langsung menghapus
    $component->call('delete', $assignment->id);
    expect(EventCommitteeAssignment::find($assignment->id))->toBeNull();
});
