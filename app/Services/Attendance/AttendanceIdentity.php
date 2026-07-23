<?php

namespace App\Services\Attendance;

use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;

class AttendanceIdentity
{
    public readonly ?int $pesertaId;
    public readonly ?int $participationId;
    public readonly ?int $personId;
    public readonly ?string $nama;

    public function __construct(
        public readonly ?Participation $participation,
        public readonly ?peserta $peserta,
        public readonly ?Person $person,
    ) {
        $this->pesertaId = $this->peserta?->id;
        $this->participationId = $this->participation?->id;
        $this->personId = $this->person?->id;
        $this->nama = $this->person?->nama ?? $this->peserta?->nama;
    }

    public function isCanonical(): bool
    {
        return $this->participation !== null;
    }

    public function isLegacy(): bool
    {
        return $this->peserta !== null;
    }

    public function isResolved(): bool
    {
        return $this->participation !== null || $this->peserta !== null;
    }
}
