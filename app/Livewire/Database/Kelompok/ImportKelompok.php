<?php

namespace App\Livewire\Database\Kelompok;

use App\Livewire\Import\ImportWizardBase;

class ImportKelompok extends ImportWizardBase
{
    protected function definitionKey(): string
    {
        return 'kelompok';
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
        return 'refreshKelompok';
    }
}
