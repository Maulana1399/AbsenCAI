<?php

namespace App\Livewire\MasterData\Person;

use App\Models\Person;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class DeletePerson extends Component
{
    public ?int $person_id = null;

    public ?string $person_nama = null;

    public ?string $blockReason = null;

    public bool $canDelete = false;

    #[On('deletePerson')]
    public function deletePerson(int $id): void
    {
        $person = Person::withCount([
            'participations',
            'legacyPesertaMapping',
            'committeeAssignments',
        ])->findOrFail($id);

        $this->person_id = $person->id;
        $this->person_nama = $person->nama;

        $hasParticipations = $person->participations_count > 0;
        $hasLegacyMapping = $person->legacyPesertaMapping()->exists();
        $hasCommitteeAssignments = $person->committee_assignments_count > 0;

        $reasons = [];

        if ($hasParticipations) {
            $reasons[] = 'Person ini terdaftar sebagai peserta di '.$person->participations_count.' event.';
        }

        if ($hasLegacyMapping) {
            $reasons[] = 'Person ini memiliki mapping data legacy yang tidak bisa dihapus.';
        }

        if ($hasCommitteeAssignments) {
            $reasons[] = 'Person ini memiliki '.$person->committee_assignments_count.' penugasan kepanitiaan.';
        }

        if (! empty($reasons)) {
            $this->blockReason = 'Person tidak dapat dihapus karena:<br>'.implode('<br>', $reasons);
            $this->canDelete = false;
        } else {
            $this->blockReason = null;
            $this->canDelete = true;
        }

        Flux::modal('hapus-person')->show();
    }

    public function destroy(): void
    {
        Gate::authorize('manage-master-data');

        $person = Person::withCount([
            'participations',
            'legacyPesertaMapping',
            'committeeAssignments',
        ])->find($this->person_id);

        if (! $person) {
            Flux::modal('hapus-person')->close();

            return;
        }

        $hasParticipations = $person->participations_count > 0;
        $hasLegacyMapping = $person->legacyPesertaMapping()->exists();
        $hasCommitteeAssignments = $person->committee_assignments_count > 0;

        if ($hasParticipations || $hasLegacyMapping || $hasCommitteeAssignments) {
            return;
        }

        $person->delete();

        $this->dispatch('refreshPerson');
        Flux::modal('hapus-person')->close();
    }

    public function render()
    {
        return view('livewire.master-data.person.delete-person');
    }
}
