<?php

namespace App\Livewire\Event;

use App\Models\Event;
use App\Support\ActiveEventContext;
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
        $context = app(ActiveEventContext::class);
        $event = $context->switchTo($eventId);

        if ($event) {
            $this->currentEventName = $event->name;
            $this->currentEventId = $event->id;
        }

        $this->dispatch('eventSwitched');
    }

    public function render()
    {
        return view('livewire.event.event-switcher', [
            'events' => Event::active()->orderBy('name')->get(),
        ]);
    }
}
