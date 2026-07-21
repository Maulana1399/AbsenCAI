<?php

namespace App\Services\Person;

use App\Models\Person;
use App\Models\peserta;
use App\Models\LegacyPesertaMapping;
use App\Services\Placement\PlacementService;
use Illuminate\Support\Facades\DB;

class PersonLegacySyncService
{
    /**
     * Sync identity fields from Person to mapped legacy peserta.
     *
     * This only runs when Person has a LegacyPesertaMapping.
     * Fields synced: nama, jenis_kelamin, desa_id, kelompok_id
     * Fields NOT synced: nip, regu_id, participant_number, attendance_code, status_registrasi
     */
    public function syncToPeserta(Person $person): void
    {
        $mapping = $person->legacyPesertaMapping()->with('peserta')->first();

        if ($mapping === null || $mapping->peserta === null) {
            return;
        }

        $peserta = $mapping->peserta;

        DB::transaction(function () use ($person, $peserta) {
            $peserta->update([
                'nama' => $person->nama,
                'jenis_kelamin' => PlacementService::normalizePersonGender($person->jenis_kelamin ?? 'L'),
                'desa_id' => $person->desa_id,
                'kelompok_id' => $person->kelompok_id,
            ]);
        });
    }

    /**
     * Check if a Person has legacy peserta mapping.
     */
    public function hasMapping(Person $person): bool
    {
        return $person->legacyPesertaMapping()->exists();
    }

    /**
     * Check if NIP change is allowed for this Person.
     * NIP is immutable for mapped Persons to preserve attendance history
     * and legacy compatibility.
     */
    public function canChangeNip(Person $person): bool
    {
        return ! $this->hasMapping($person);
    }

    /**
     * Server-side enforcement: return the NIP value that should be persisted.
     *
     * For mapped Persons, always returns the existing database NIP
     * regardless of the submitted value, preventing manipulation via
     * Livewire state tampering.
     *
     * For standalone Persons, returns the submitted value.
     */
    public function resolveNip(Person $person, mixed $submittedNip): ?string
    {
        if ($this->hasMapping($person)) {
            return $person->nip;
        }

        return $submittedNip ? (string) $submittedNip : null;
    }
}
