<?php

namespace App\Livewire\Dashboard;

use App\Models\Event;
use App\Enums\Role;
use App\Services\Event\EventAccessService;
use App\Support\ActiveEventContext;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.platform')]
class PlatformDashboard extends Component
{
    public function getEventsProperty()
    {
        $user = auth()->user();

        if ($user->role === Role::KetuaEvent) {
            $ids = app(EventAccessService::class)->getAssignedEventIds($user);
            return Event::active()->whereIn('id', $ids)
                ->withCount('participations')
                ->with(['sesiAbsensis' => fn ($q) => $q->where('aktif', true)])
                ->orderBy('name')
                ->get();
        }

        return Event::active()
            ->withCount('participations')
            ->with(['sesiAbsensis' => fn ($q) => $q->where('aktif', true)])
            ->orderBy('name')
            ->get();
    }

    public function openEvent(int $eventId)
    {
        $event = Event::active()->findOrFail($eventId);
        app(ActiveEventContext::class)->set($event);

        $route = match (true) {
            $event->isPengajian() => route('pengajian.report', absolute: false),
            $event->isCompetition() => route('competition.dashboard', $event, absolute: false),
            default => route('events.dashboard', $event, absolute: false),
        };

        $this->redirect($route, navigate: true);
    }

    public function render()
    {
        $user = auth()->user();
        $events = $this->events;

        $userRole = $user->role
            ? $user->role->label()
            : 'Tidak ada peran';

        $version = 'v1.0';

        return view('livewire.dashboard.platform-dashboard', [
            'events' => $events,
            'activeCount' => $events->count(),
            'userRole' => $userRole,
            'version' => $version,
        ]);
    }
}
