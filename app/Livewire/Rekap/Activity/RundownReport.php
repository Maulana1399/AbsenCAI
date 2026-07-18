<?php

namespace App\Livewire\Rekap\Activity;

use App\Models\RundownItem;
use App\Support\ActiveEventContext;
use Livewire\Component;

class RundownReport extends Component
{
    public function render()
    {
        $event = app(ActiveEventContext::class)->current();
        $query = RundownItem::with(['rundown', 'activity.activityGroup', 'venue'])
            ->when($event !== null, fn ($builder) => $builder->where('event_id', $event->id));

        return view('livewire.rekap.activity.rundown-report', [
            'rows' => $query->orderBy('starts_at')->get(),
        ]);
    }
}
