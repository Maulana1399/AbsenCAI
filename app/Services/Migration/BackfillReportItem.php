<?php

namespace App\Services\Migration;

use App\Models\peserta;

class BackfillReportItem
{
    public int $pesertaId;
    public ?int $nip;
    public string $pesertaNama;
    public string $outcome = 'SKIPPED';
    public array $messages = [];

    public ?array $projectedPersonData = null;
    public ?array $projectedParticipationData = null;
    public ?array $projectedMappingData = null;

    public int $peopleCreated = 0;
    public int $participationsCreated = 0;
    public int $mappingsCreated = 0;

    public function __construct(peserta $peserta)
    {
        $this->pesertaId = $peserta->id;
        $this->nip = $peserta->nip;
        $this->pesertaNama = $peserta->nama;
    }
}
