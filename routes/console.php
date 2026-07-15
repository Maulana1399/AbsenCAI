<?php

use App\Models\peserta;
use App\Services\Placement\PlacementService;
use App\Services\Registration\RegistrationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('kja:identity-backfill', function () {
    $updated = 0;
    $skipped = 0;
    $failed = 0;

    $registrationService = app(RegistrationService::class);

    peserta::query()->orderBy('id')->chunkById(100, function ($participants) use (&$updated, &$skipped, &$failed, $registrationService) {
        foreach ($participants as $participant) {
            try {
                $needsParticipantNumber = is_null($participant->participant_number) || $participant->participant_number === 'participant_number';
                $needsAttendanceCode = is_null($participant->attendance_code) || $participant->attendance_code === 'attendance_code';

                if (! $needsParticipantNumber && ! $needsAttendanceCode) {
                    $skipped++;
                    continue;
                }

                $payload = [];

                if ($needsParticipantNumber) {
                    $payload['participant_number'] = PlacementService::generateParticipantNumber($participant->jenis_kelamin);
                }

                if ($needsAttendanceCode) {
                    $payload['attendance_code'] = $registrationService->generateAttendanceCode();
                }

                $participant->update($payload);
                $updated++;
            } catch (\Throwable $throwable) {
                $failed++;

                $this->line('FAILED');
                $this->line('ID: '.$participant->id);
                $this->line('Name: '.$participant->nama);
                $this->line('Error:');
                $this->line($throwable->getMessage());

                $trace = $throwable->getTrace();
                if (! empty($trace[0]['file']) && ! empty($trace[0]['line'])) {
                    $this->line('Location: '.$trace[0]['file'].':'.$trace[0]['line']);
                }

                $this->line('');
            }
        }
    });

    $this->info("Updated: {$updated}");
    $this->info("Skipped: {$skipped}");
    $this->info("Failed: {$failed}");
})->purpose('Repair legacy participant identity values');
