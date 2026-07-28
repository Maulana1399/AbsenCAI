<?php

namespace App\Livewire\Competition\Report;

use App\Models\Venue;
use App\Services\Competition\CompetitionReportService;
use App\Support\ActiveEventContext;
use Livewire\Component;

class Schedule extends Component
{
    public string $filterVenueId = '';
    public string $filterStatus = '';

    public function render()
    {
        $event = app(ActiveEventContext::class)->current();

        $schedules = app(CompetitionReportService::class)->scheduleReport($event, [
            'venue_id' => $this->filterVenueId,
            'status' => $this->filterStatus,
        ]);

        return view('livewire.competition.report.schedule', [
            'schedules' => $schedules,
            'venues' => Venue::where('event_id', $event?->id)->orderBy('name')->get(),
        ]);
    }
}
