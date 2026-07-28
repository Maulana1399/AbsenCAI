<?php

namespace App\Exports;

use App\Services\Competition\CompetitionReportService;
use App\Models\Event;
use Illuminate\Support\Collection;

class CompetitionExport
{
    public function __construct(
        private readonly CompetitionReportService $reportService,
    ) {}

    public function registrationCsv(Event $event, array $filters = []): string
    {
        $data = $this->reportService->registrationReport($event, $filters);
        return $this->toCsv($this->formatRegistrationRows($data));
    }

    public function outcomeCsv(Event $event, array $filters = []): string
    {
        $data = $this->reportService->outcomeReport($event, $filters);
        return $this->toCsv($this->formatOutcomeRows($data));
    }

    public function scheduleCsv(Event $event, array $filters = []): string
    {
        $data = $this->reportService->scheduleReport($event, $filters);
        return $this->toCsv($this->formatScheduleRows($data));
    }

    private function formatRegistrationRows(Collection $data): array
    {
        $rows = [['No', 'No. Peserta', 'Nama', 'Kategori', 'Kelas', 'Gender', 'Desa', 'Kelompok', 'Tanggal Daftar']];

        foreach ($data as $i => $reg) {
            $rows[] = [
                $i + 1,
                $reg->participation?->participant_number ?? '',
                $reg->participation?->person?->nama ?? '',
                $reg->competitionCategory?->name ?? '',
                $reg->competitionClass?->name ?? '',
                $reg->participation?->person?->jenis_kelamin_label ?? '',
                $reg->participation?->person?->desa?->desa_asal ?? '',
                $reg->participation?->person?->kelompok?->kelompok_asal ?? '',
                $reg->created_at?->format('d/m/Y') ?? '',
            ];
        }

        return $rows;
    }

    private function formatOutcomeRows(Collection $data): array
    {
        $rows = [['No', 'Kategori', 'Kelas', 'Peserta', 'Posisi', 'Skor', 'Status', 'Keterangan']];

        foreach ($data as $i => $outcome) {
            $rows[] = [
                $i + 1,
                $outcome->competitionRegistration?->competitionCategory?->name ?? '',
                $outcome->competitionRegistration?->competitionClass?->name ?? '',
                $outcome->competitionRegistration?->participation?->person?->nama ?? '',
                $outcome->position ?? '',
                $outcome->score ?? '',
                $outcome->status ?? '',
                $outcome->remarks ?? '',
            ];
        }

        return $rows;
    }

    private function formatScheduleRows(Collection $data): array
    {
        $rows = [['No', 'Kategori', 'Kelas', 'Venue', 'Mulai', 'Selesai', 'Status']];

        foreach ($data as $i => $schedule) {
            $rows[] = [
                $i + 1,
                $schedule->competitionClass?->competitionCategory?->name ?? '',
                $schedule->competitionClass?->name ?? '',
                $schedule->venue?->name ?? '',
                $schedule->start_at ? $schedule->start_at->format('d/m/Y H:i') : '',
                $schedule->end_at ? $schedule->end_at->format('d/m/Y H:i') : '',
                $schedule->status ?? '',
            ];
        }

        return $rows;
    }

    private function toCsv(array $rows): string
    {
        $output = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
