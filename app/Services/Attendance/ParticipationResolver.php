<?php

namespace App\Services\Attendance;

use App\Models\Participation;
use App\Models\peserta;

class ParticipationResolver
{
    public function __construct(
        private readonly LegacyParticipationResolver $legacyParticipationResolver,
    ) {}

    public function resolveByPeserta(peserta $peserta, int $eventId): ?Participation
    {
        return $this->legacyParticipationResolver->resolveByPesertaAndEvent($peserta->id, $eventId);
    }

    public function resolveByNip(int $nip, int $eventId): ?Participation
    {
        $peserta = peserta::where('nip', $nip)->first();

        if ($peserta === null) {
            return null;
        }

        return $this->legacyParticipationResolver->resolveByPesertaAndEvent($peserta->id, $eventId);
    }

    public function resolveByPesertaId(?int $pesertaId, int $eventId): ?Participation
    {
        if ($pesertaId === null) {
            return null;
        }

        return $this->legacyParticipationResolver->resolveByPesertaAndEvent($pesertaId, $eventId);
    }
}
