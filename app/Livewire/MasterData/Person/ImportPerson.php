<?php

namespace App\Livewire\MasterData\Person;

use App\Livewire\Import\ImportWizardBase;

class ImportPerson extends ImportWizardBase
{
    protected function definitionKey(): string
    {
        return 'person';
    }

    protected function gateAbility(): string
    {
        return 'manage-master-data';
    }

    protected function steps(): array
    {
        return ['Upload', 'Preview', 'Validation', 'Import', 'Result'];
    }

    protected function refreshEvent(): ?string
    {
        return 'refreshPerson';
    }
}
