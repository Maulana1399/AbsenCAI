<?php

namespace App\Services\Activity;

use App\Models\Activity;
use App\Models\Event;
use App\Models\Rundown;
use App\Models\RundownItem;
use App\Models\Venue;
use Illuminate\Validation\ValidationException;

class ActivityScheduleService
{
    public function createVenue(array $data): Venue
    {
        $event = Event::findOrFail($data['event_id']);

        return Venue::create([
            'event_id' => $event->id,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'location_detail' => $data['location_detail'] ?? null,
            'sort_order' => $data['sort_order'] ?? null,
        ]);
    }

    public function createRundown(array $data): Rundown
    {
        $event = Event::findOrFail($data['event_id']);

        return Rundown::create([
            'event_id' => $event->id,
            'name' => $data['name'],
            'rundown_date' => $data['rundown_date'] ?? null,
            'status' => $data['status'] ?? 'draft',
        ]);
    }

    public function createRundownItem(array $data): RundownItem
    {
        $rundown = Rundown::findOrFail($data['rundown_id']);
        $activity = Activity::findOrFail($data['activity_id']);
        $venue = array_key_exists('venue_id', $data) && $data['venue_id'] !== null ? Venue::findOrFail($data['venue_id']) : null;

        if ((int) $activity->event_id !== (int) $rundown->event_id) {
            throw ValidationException::withMessages([
                'activity_id' => 'Activity must belong to the same event as rundown.',
            ]);
        }

        if ($venue !== null && (int) $venue->event_id !== (int) $rundown->event_id) {
            throw ValidationException::withMessages([
                'venue_id' => 'Venue must belong to the same event as rundown.',
            ]);
        }

        return RundownItem::create([
            'event_id' => $rundown->event_id,
            'rundown_id' => $rundown->id,
            'activity_id' => $activity->id,
            'venue_id' => $venue?->id,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'sequence' => $data['sequence'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
