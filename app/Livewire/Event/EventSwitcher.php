<?php

namespace App\Livewire\Event;

use App\Models\Event;
use App\Services\Event\EventAccessService;
use App\Support\ActiveEventContext;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Component;

class EventSwitcher extends Component
{
    public ?string $currentEventName = null;
    public ?int $currentEventId = null;

    public function mount(ActiveEventContext $context): void
    {
        $current = $context->current();

        if ($current) {
            $this->currentEventName = $current->name;
            $this->currentEventId = $current->id;
        }
    }

    public function switchTo(int $eventId): void
    {
        $user = auth()->user();

        if (! $user->isPlatformUser()) {
            $eventAccess = app(EventAccessService::class);
            if (! $eventAccess->isUserAssignedToEvent($user, $eventId)) {
                throw new AuthorizationException('Anda tidak memiliki akses ke event ini.');
            }
        }

        $context = app(ActiveEventContext::class);
        $event = $context->switchTo($eventId);

        if ($event) {
            $this->currentEventName = $event->name;
            $this->currentEventId = $event->id;

            $this->redirect($event->dashboardRoute(), navigate: true);
        }

        $this->dispatch('eventSwitched');
    }

    public function getEventsProperty()
    {
        $user = auth()->user();

        if (! $user->isPlatformUser()) {
            $assignedEventIds = app(EventAccessService::class)->getAssignedEventIds($user);
            return Event::active()->whereIn('id', $assignedEventIds)->orderBy('name')->get();
        }

        return Event::active()->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.event.event-switcher', [
            'events' => $this->events,
        ]);
    }
}
