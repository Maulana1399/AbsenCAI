<?php

namespace App\Livewire\Competition\Report;

use App\Services\Competition\CompetitionReportService;
use App\Support\ActiveEventContext;
use Livewire\Component;

class Summary extends Component
{
    public function render()
    {
        $event = app(ActiveEventContext::class)->current();
        $data = app(CompetitionReportService::class)->summary($event);

        return view('livewire.competition.report.summary', $data);
    }
}
