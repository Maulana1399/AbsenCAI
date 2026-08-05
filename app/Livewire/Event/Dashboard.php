<?php

namespace App\Livewire\Event;

use App\Livewire\Traits\ResolvesEventDashboard;
use App\Models\Event;
use App\Models\SesiAbsensi;
use App\Services\Dashboard\DashboardPresenterFactory;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Dashboard extends Component
{
    use ResolvesEventDashboard;

    public string $eventName = '';

    public function mount(Event $event, DashboardPresenterFactory $factory): void
    {
        $this->resolveEventDashboard($event);
    }

    public function render(DashboardPresenterFactory $factory)
    {
        $event = app(ActiveEventContext::class)->current();

        $presenter = $factory->make($event);
        $data = $presenter->present($event);

        return view('livewire.event.dashboard', [
            'event' => $event,
            'eventName' => $this->eventName,
            'presenterView' => $presenter->view(),
            'presenterData' => $data,
        ]);
    }

    public function activateSesi(int $id): void
    {
        Gate::authorize('manage-sessions');

        $event = app(ActiveEventContext::class)->requireCurrent();

        SesiAbsensi::where('event_id', $event->id)->update(['aktif' => false]);
        SesiAbsensi::where('event_id', $event->id)->where('id', $id)->update(['aktif' => true]);
    }
}
