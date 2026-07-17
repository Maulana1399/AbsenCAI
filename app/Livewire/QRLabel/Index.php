<?php

namespace App\Livewire\QRLabel;

use App\Models\peserta;
use App\Services\Audit\ActivityLogService;
use App\Services\Print\PrintEngine;
use App\Services\QR\BatchQRExportService;
use App\Services\QR\QRService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;

class Index extends Component
{
    public string $search = '';
    public string $mode = 'individual';
    public ?int $selectedParticipantId = null;

    public $selectedParticipant;

    public $filterDesa = '';
    public $filterKelompok = '';
    public $filterRegu = '';
    public $filterGender = '';
    public string $filterKeyword = '';

    public $daftarDesa = [];
    public $daftarKelompok = [];
    public $daftarRegu = [];
    public $batchPreview = [];
    public $batchParticipants = [];
    public $labelPreview = [];
    public ?int $selectedLabelParticipantId = null;
    public string $labelPreviewHtml = '';

    public function mount(): void
    {
        $this->daftarDesa = \App\Models\desa::orderBy('desa_asal')->get();
        $this->daftarKelompok = \App\Models\kelompok::with('desa')->orderBy('kelompok_asal')->get();
        $this->daftarRegu = \App\Models\regu::orderBy('regu')->get();
        $this->refreshBatchAndLabelPreview();
    }

    public function selectLabelParticipant(int $participantId): void
    {
        $participant = peserta::findOrFail($participantId);

        $this->selectedLabelParticipantId = $participant->id;
        $this->labelPreviewHtml = app(PrintEngine::class)->label4x4($participant);
    }

    public function updatedSearch(): void
    {
        $this->selectedParticipantId = null;
        $this->selectedParticipant = null;
    }

    public function updated($name): void
    {
        if (str_starts_with($name, 'filter')) {
            $this->refreshBatchAndLabelPreview();
            $this->syncLabelSelectionToFilteredParticipants();
        }
    }

    public function selectParticipant(int $participantId): void
    {
        $participant = peserta::findOrFail($participantId);
        $this->selectedParticipantId = $participant->id;
        $this->selectedParticipant = $participant;

        if ($this->mode === 'label') {
            $this->labelPreviewHtml = app(PrintEngine::class)->label4x4($participant);
        }
    }

    public function downloadPng()
    {
        $participant = $this->requireSelectedParticipant();
        $content = app(QRService::class)->generatePng((string) $participant->attendance_code);
        $filename = $participant->participant_number.'.png';

        app(ActivityLogService::class)->log(
            action: 'downloaded',
            module: 'qr',
            description: 'Mengunduh QR peserta '.$participant->nama,
            subject: $participant,
            properties: [
                'qr_type'           => 'single',
                'peserta_id'        => $participant->id,
                'participant_number' => $participant->participant_number,
                'attendance_code'   => $participant->attendance_code,
                'format'            => 'png',
                'filename'          => $filename,
            ],
        );

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'image/png',
        ]);
    }

    // public function downloadSvg()
    // {
    //     $participant = $this->requireSelectedParticipant();
    //     $content = app(QRService::class)->generateSvg((string) $participant->attendance_code);

    //     return response()->streamDownload(function () use ($content) {
    //         echo $content;
    //     }, $participant->participant_number.'.svg', [
    //         'Content-Type' => 'image/svg+xml',
    //     ]);
    // }

    public function generateBatchExport(): void
    {
        $participants = $this->filteredParticipants();
        $summary = app(BatchQRExportService::class)->export($participants, 'png', 'qr-exports');

        app(ActivityLogService::class)->log(
            action: 'batch_exported',
            module: 'qr',
            description: 'Membuat batch QR peserta',
            properties: [
                'qr_type'      => 'batch',
                'format'       => $summary['format'],
                'record_count' => $summary['generated'],
                'skipped'      => $summary['skipped'],
                'failed'       => $summary['failed'],
                'directory'    => $summary['directory'],
            ],
        );

        $this->batchPreview = $summary;
        $this->batchParticipants = $participants->values();
    }

    public function refreshBatchAndLabelPreview(): void
    {
        $participants = $this->filteredParticipants();
        $this->batchPreview = [
            'generated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'paths' => [],
            'directory' => 'qr-exports',
            'format' => 'png',
        ];
        $this->batchParticipants = $participants->values();
        $this->syncLabelPreview($participants);
    }

    private function syncLabelPreview(?Collection $participants = null): void
    {
        $participants ??= $this->filteredParticipants();
        $this->labelPreview = $participants->values();

        if ($participants->isEmpty()) {
            $this->selectedLabelParticipantId = null;
            $this->labelPreviewHtml = '';
            return;
        }

        $selected = $this->selectedLabelParticipantId
            ? $participants->firstWhere('id', $this->selectedLabelParticipantId)
            : null;

        if (! $selected) {
            $selected = $participants->first();
        }

        $this->selectedLabelParticipantId = $selected->id;
        $this->labelPreviewHtml = app(PrintEngine::class)->label4x4($selected);
    }

    private function syncLabelSelectionToFilteredParticipants(): void
    {
        $this->syncLabelPreview($this->filteredParticipants());
    }

    public function render()
    {
        $batchParticipants = $this->batchParticipants ?: $this->filteredParticipants();

        return view('livewire.qr-label.index', [
            'results' => $this->participantSearchResults(),
            'batchParticipants' => $batchParticipants,
            'batchTotal' => $batchParticipants->count(),
        ]);
    }

    private function participantSearchResults(): Collection
    {
        $query = peserta::with(['desa', 'kelompok', 'regu']);

        $keyword = trim($this->search);
        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder->where('nama', 'like', '%'.$keyword.'%')
                    ->orWhere('participant_number', 'like', '%'.$keyword.'%')
                    ->orWhere('attendance_code', 'like', '%'.$keyword.'%');
            });
        }

        return $query->orderBy('nama')->limit(10)->get();
    }

    private function filteredParticipants(): Collection
    {
        $query = peserta::with(['desa', 'kelompok', 'regu'])->whereNotNull('attendance_code');

        if ($this->filterDesa !== '') {
            $query->where('desa_id', $this->filterDesa);
        }

        if ($this->filterKelompok !== '') {
            $query->where('kelompok_id', $this->filterKelompok);
        }

        if ($this->filterRegu !== '') {
            $query->where('regu_id', $this->filterRegu);
        }

        if ($this->filterGender !== '') {
            $query->where('jenis_kelamin', $this->filterGender);
        }

        $keyword = trim($this->filterKeyword);
        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder->where('nama', 'like', '%'.$keyword.'%')
                    ->orWhere('participant_number', 'like', '%'.$keyword.'%')
                    ->orWhere('attendance_code', 'like', '%'.$keyword.'%');
            });
        }

        return $query->orderBy('nama')->get();
    }

    public function printSelectedLabel()
    {
        $participant = $this->requireSelectedParticipant();
        return response($this->printHtmlForParticipants(collect([$participant])))->header('Content-Type', 'text/html');
    }

    public function printAllFiltered()
    {
        $participants = $this->filteredParticipants();

        if ($participants->isEmpty()) {
            abort(404);
        }

        return response($this->printHtmlForParticipants($participants))->header('Content-Type', 'text/html');
    }

    private function printHtmlForParticipants(Collection $participants): string
    {
        $qrService = app(QRService::class);

        $pages = $participants->map(function ($participant) use ($qrService) {
            $qrBase64 = base64_encode($qrService->generatePng((string) $participant->attendance_code));
            $participantNumber = htmlspecialchars((string) $participant->participant_number, ENT_QUOTES, 'UTF-8');
            $participantName = htmlspecialchars((string) $participant->nama, ENT_QUOTES, 'UTF-8');

            return <<<HTML
<div class="label-page">
    <div class="label">
        <div class="participant-number">{$participantNumber}</div>
        <div class="qr"><img src="data:image/png;base64,{$qrBase64}" alt="QR Code"></div>
        <div class="participant-name">{$participantName}</div>
    </div>
</div>
HTML;
        })->implode('');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: 4cm 4cm; margin: 0; }
        html, body { margin: 0; padding: 0; }
        .label-page { width: 4cm; height: 4cm; page-break-after: always; break-after: page; }
        .label { width: 4cm; height: 4cm; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 2px; padding: 2mm; box-sizing: border-box; font-family: Arial, sans-serif; }
        .participant-number { font-size: 10pt; font-weight: bold; }
        .participant-name { font-size: 8pt; line-height: 1.1; }
        .qr { width: 1.8cm; height: 1.8cm; }
        .qr img { width: 100%; height: 100%; object-fit: contain; }
    </style>
</head>
<body>{$pages}</body>
</html>
HTML;
    }

    private function requireSelectedParticipant(): peserta
    {
        if (! $this->selectedParticipantId) {
            abort(404);
        }

        return peserta::findOrFail($this->selectedParticipantId);
    }
}
