<?php

namespace App\Livewire\Competition;

use App\Models\Event;
use App\Models\CompetitionCategory;
use App\Models\CompetitionClass;
use App\Models\CompetitionRegistration;
use App\Models\Venue;
use App\Services\Event\EventAccessService;
use App\Support\ActiveEventContext;
use Livewire\Component;

class Dashboard extends Component
{
    public string $eventName;

    public function mount(Event $event)
    {
        abort_unless($event->isActive(), 404);

        abort_unless(
            app(EventAccessService::class)->canAccess(auth()->user(), $event),
            403
        );

        app(ActiveEventContext::class)->set($event);

        $this->eventName = $event->name;
    }

    public function render()
    {
        $event = app(ActiveEventContext::class)->current();

        $totalRegistrations = CompetitionRegistration::whereIn(
            'competition_category_id',
            CompetitionCategory::where('event_id', $event?->id)->pluck('id')
        )->count();

        $totalCategories = CompetitionCategory::where('event_id', $event?->id)->count();
        $totalClasses = CompetitionClass::where('event_id', $event?->id)->count();
        $totalVenues = Venue::where('event_id', $event?->id)->count();

        return view('livewire.competition.dashboard', [
            'totalRegistrations' => $totalRegistrations,
            'totalCategories' => $totalCategories,
            'totalClasses' => $totalClasses,
            'totalVenues' => $totalVenues,
            'eventName' => $this->eventName,
        ]);
    }
}
