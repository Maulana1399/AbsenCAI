<?php

namespace App\Livewire\Database\Regu;

use App\Livewire\Import\ImportWizardBase;

class ImportRegu extends ImportWizardBase
{
    protected function definitionKey(): string
    {
        return 'regu';
    }

    protected function gateAbility(): string
    {
        return 'manage-participants';
    }

    protected function steps(): array
    {
        return ['Upload', 'Preview', 'Validation', 'Import', 'Result'];
    }

    protected function refreshEvent(): ?string
    {
        return 'refreshRegu';
    }
}
