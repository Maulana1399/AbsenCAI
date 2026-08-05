<?php

namespace App\Services\QR;

use App\Models\Participation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class BatchQRExportService
{
    public function __construct(
        private readonly QRService $qrService,
    ) {}

    public function export(Collection $participants, string $format = 'png', string $directory = 'qr-exports'): array
    {
        $generated = 0;
        $skipped = 0;
        $failed = 0;
        $paths = [];

        Storage::makeDirectory($directory);

        foreach ($participants as $participant) {
            if (! $participant instanceof Participation) {
                $failed++;

                continue;
            }

            if (blank($participant->attendance_code) || blank($participant->participant_number)) {
                $skipped++;

                continue;
            }

            try {
                $filename = $participant->participant_number.'.'.$this->extension($format);
                $path = trim($directory, '/').'/'.$filename;
                $content = $this->renderContent($participant->attendance_code, $format);

                Storage::put($path, $content);

                $paths[] = $path;
                $generated++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        return [
            'generated' => $generated,
            'skipped' => $skipped,
            'failed' => $failed,
            'paths' => $paths,
            'directory' => $directory,
            'format' => strtolower($format),
        ];
    }

    private function renderContent(string $attendanceCode, string $format): string
    {
        return strtolower($format) === 'svg'
            ? $this->qrService->generateSvg($attendanceCode)
            : $this->qrService->generatePng($attendanceCode);
    }

    private function extension(string $format): string
    {
        return strtolower($format) === 'svg' ? 'svg' : 'png';
    }
}
