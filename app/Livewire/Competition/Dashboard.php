<?php

namespace App\Livewire\Competition;

use App\Livewire\Traits\ResolvesEventDashboard;
use App\Models\Event;
use App\Services\Dashboard\CompetitionDashboardPresenter;
use App\Support\ActiveEventContext;
use Livewire\Component;

class Dashboard extends Component
{
    use ResolvesEventDashboard;

    public string $eventName = '';

    public function mount(Event $event): void
    {
        $this->resolveEventDashboard($event);
    }

    public function render(CompetitionDashboardPresenter $presenter)
    {
        $event = app(ActiveEventContext::class)->current();

        $data = $presenter->present($event);

        return view('livewire.competition.dashboard', array_merge($data, [
            'eventName' => $this->eventName,
        ]));
    }
}
