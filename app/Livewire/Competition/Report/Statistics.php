<?php

namespace App\Livewire\Competition\Report;

use App\Services\Competition\CompetitionReportService;
use App\Support\ActiveEventContext;
use Livewire\Component;

class Statistics extends Component
{
    public function render()
    {
        $event = app(ActiveEventContext::class)->current();
        $service = app(CompetitionReportService::class);

        return view('livewire.competition.report.statistics', [
            'venueStats' => $service->venueStatistics($event),
            'categoryStats' => $service->categoryStatistics($event),
            'classStats' => $service->classStatistics($event),
        ]);
    }
}
