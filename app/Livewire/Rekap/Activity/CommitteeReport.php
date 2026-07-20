<?php

namespace App\Livewire\Rekap\Activity;

use App\Models\ActivityCommitteeAssignment;
use App\Support\ActiveEventContext;
use Livewire\Component;

class CommitteeReport extends Component
{
    public function render()
    {
        $event = app(ActiveEventContext::class)->current();
        $query = ActivityCommitteeAssignment::with(['person', 'participation', 'eventRole', 'activityGroup', 'activity', 'venue']);

        if ($event !== null) {
            $query->where('event_id', $event->id);
        } else {
            $query->whereRaw('0 = 1');
        }

        return view('livewire.rekap.activity.committee-report', [
            'rows' => $query->orderBy('id')->get(),
        ]);
    }
}
