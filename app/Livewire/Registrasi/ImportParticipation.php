<?php

namespace App\Livewire\Registrasi;

use App\Livewire\Import\ImportWizardBase;
use App\Support\ActiveEventContext;

class ImportParticipation extends ImportWizardBase
{
    protected function definitionKey(): string
    {
        return 'participation';
    }

    protected function gateAbility(): string
    {
        return 'manage-registration';
    }

    protected function steps(): array
    {
        return ['Upload', 'Preview', 'Validation', 'Import', 'Result'];
    }

    protected function refreshEvent(): ?string
    {
        return null;
    }

    protected function templateUrl(): string
    {
        return route('import.participation.template', ['event' => app(ActiveEventContext::class)->id()]);
    }

    public function mount(): void
    {
        if (empty($this->parameters['event_id'])) {
            $eventId = app(ActiveEventContext::class)->id();

            if ($eventId !== null) {
                $this->parameters['event_id'] = (string) $eventId;
            }
        }
    }
}
