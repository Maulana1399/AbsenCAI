<?php

namespace App\Services\Activity;

use App\Models\Activity;
use App\Models\ActivityGroup;
use App\Models\ActivityRegistration;
use App\Models\Event;
use App\Models\Participation;
use Illuminate\Validation\ValidationException;

class ActivityRegistrationService
{
    public function create(array $data): ActivityRegistration
    {
        $participation = Participation::with('event')->findOrFail($data['participation_id']);
        $activity = Activity::with('event')->findOrFail($data['activity_id']);

        if ((int) $participation->event_id !== (int) $activity->event_id) {
            throw ValidationException::withMessages([
                'activity_id' => 'Activity must belong to the same event as participation.',
            ]);
        }

        $eventId = (int) $participation->event_id;

        return ActivityRegistration::create([
            'event_id' => $eventId,
            'participation_id' => $participation->id,
            'activity_id' => $activity->id,
            'status' => $data['status'] ?? 'registered',
            'registered_at' => $data['registered_at'] ?? now(),
            'source' => $data['source'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function createGroup(array $data): ActivityGroup
    {
        $event = Event::findOrFail($data['event_id']);

        return ActivityGroup::create([
            'event_id' => $event->id,
            'name' => $data['name'],
            'slug' => $data['slug'] ?? null,
            'sort_order' => $data['sort_order'] ?? null,
        ]);
    }

    public function createActivity(array $data): Activity
    {
        $event = Event::findOrFail($data['event_id']);
        $group = null;

        if (array_key_exists('activity_group_id', $data) && $data['activity_group_id'] !== null) {
            $group = ActivityGroup::findOrFail($data['activity_group_id']);
            if ((int) $group->event_id !== (int) $event->id) {
                throw ValidationException::withMessages([
                    'activity_group_id' => 'Activity group must belong to the same event as activity.',
                ]);
            }
        }

        return Activity::create([
            'event_id' => $event->id,
            'activity_group_id' => $group?->id,
            'name' => $data['name'],
            'slug' => $data['slug'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);
    }
}
