<?php

namespace App\Services\Activity;

use App\Models\Activity;
use App\Models\ActivityGroup;
use App\Models\Event;
use App\Models\EventCommitteeAssignment;
use App\Models\EventRole;
use App\Models\Participation;
use App\Models\Person;
use App\Models\Venue;
use Illuminate\Validation\ValidationException;

class EventCommitteeService
{
    public function createRole(array $data): EventRole
    {
        $event = Event::findOrFail($data['event_id']);

        return EventRole::create([
            'event_id' => $event->id,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'scope' => $data['scope'] ?? null,
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function assign(array $data): EventCommitteeAssignment
    {
        $event = Event::findOrFail($data['event_id']);
        $role = EventRole::findOrFail($data['event_role_id']);
        $person = Person::findOrFail($data['person_id']);
        $participation = array_key_exists('participation_id', $data) && $data['participation_id'] !== null
            ? Participation::findOrFail($data['participation_id'])
            : null;
        $activityGroup = array_key_exists('activity_group_id', $data) && $data['activity_group_id'] !== null
            ? ActivityGroup::findOrFail($data['activity_group_id'])
            : null;
        $activity = array_key_exists('activity_id', $data) && $data['activity_id'] !== null
            ? Activity::findOrFail($data['activity_id'])
            : null;
        $venue = array_key_exists('venue_id', $data) && $data['venue_id'] !== null
            ? Venue::findOrFail($data['venue_id'])
            : null;

        if ((int) $role->event_id !== (int) $event->id) {
            throw ValidationException::withMessages(['event_role_id' => 'Role must belong to the same event.']);
        }

        if ($participation !== null && (int) $participation->event_id !== (int) $event->id) {
            throw ValidationException::withMessages(['participation_id' => 'Participation must belong to the same event.']);
        }

        if ($participation !== null && (int) $participation->person_id !== (int) $person->id) {
            throw ValidationException::withMessages(['person_id' => 'Participation must belong to the same person.']);
        }

        if ($activityGroup !== null && (int) $activityGroup->event_id !== (int) $event->id) {
            throw ValidationException::withMessages(['activity_group_id' => 'Activity group must belong to the same event.']);
        }

        if ($activity !== null && (int) $activity->event_id !== (int) $event->id) {
            throw ValidationException::withMessages(['activity_id' => 'Activity must belong to the same event.']);
        }

        if ($venue !== null && (int) $venue->event_id !== (int) $event->id) {
            throw ValidationException::withMessages(['venue_id' => 'Venue must belong to the same event.']);
        }

        $exists = EventCommitteeAssignment::query()
            ->where('event_id', $event->id)
            ->where('person_id', $person->id)
            ->where('event_role_id', $role->id)
            ->where('participation_id', $participation?->id)
            ->where('activity_group_id', $activityGroup?->id)
            ->where('activity_id', $activity?->id)
            ->where('venue_id', $venue?->id)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['person_id' => 'Duplicate committee assignment.']);
        }

        return EventCommitteeAssignment::create([
            'event_id' => $event->id,
            'person_id' => $person->id,
            'participation_id' => $participation?->id,
            'event_role_id' => $role->id,
            'activity_group_id' => $activityGroup?->id,
            'activity_id' => $activity?->id,
            'venue_id' => $venue?->id,
            'assigned_at' => $data['assigned_at'] ?? now(),
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
