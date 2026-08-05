<?php

namespace App\Services\Person;

use App\Models\LegacyPesertaMapping;
use App\Models\Person;
use App\Models\peserta;
use App\Services\Placement\PlacementService;
use Illuminate\Support\Facades\DB;

class PersonLegacySyncService
{
    /**
     * Sync identity fields from Person to mapped legacy peserta.
     *
     * This only runs when Person has a LegacyPesertaMapping.
     * Fields synced: nama, jenis_kelamin, desa_id, kelompok_id
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
}
