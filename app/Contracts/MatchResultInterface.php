<?php

namespace App\Contracts;

use App\Models\CompetitionSchedule;

interface MatchResultInterface
{
    public function submitResult(CompetitionSchedule $schedule, array $data): void;

    public function validateResult(CompetitionSchedule $schedule, array $data): array;

    public function finalizeResult(CompetitionSchedule $schedule): void;
}
