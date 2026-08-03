<?php

namespace App\Livewire\Traits;

/**
 * Shared result handling for Pengajian manual entry (operator & admin).
 *
 * Maps the registration result array onto the component's public state.
 * Both ManualEntry components expose the same public properties and reuse
 * this identical switch logic.
 */
trait HandlesManualEntryResult
{
    private function handleResult(array $result): void
    {
        $this->processing = false;

        switch ($result['status']) {
            case 'created':
            case 'matched':
                $this->step = 3;
                $this->successMessage = $result['message'];
                $this->resultPersonName = $result['person']->nama;
                $this->resultParticipantNumber = $result['participation']->participant_number;
                $this->resultPersonId = $result['person']->id;
                $this->resultParticipationId = $result['participation']->id;
                break;

            case 'duplicate':
                $this->errorMessage = $result['message'];
                break;

            case 'ambiguous':
                $this->step = 2;
                $this->potentialMatches = $result['potential_matches'];
                $this->errorMessage = $result['message'];
                break;

            default:
                $this->errorMessage = 'Hasil tidak dikenali.';
        }
    }
}
