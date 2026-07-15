<?php

namespace App\Livewire\QRLabel;

use App\Models\peserta;
use App\Services\Print\PrintEngine;
use App\Services\QR\BatchQRExportService;
use App\Services\QR\QRService;
use Illuminate\Support\Collection;
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
    public $labelPreview = [];
    public string $labelPreviewHtml = '';

    public function mount(): void
    {
        $this->daftarDesa = \App\Models\desa::orderBy('desa_asal')->get();
        $this->daftarKelompok = \App\Models\kelompok::with('desa')->orderBy('kelompok_asal')->get();
        $this->daftarRegu = \App\Models\regu::orderBy('regu')->get();
        $this->refreshBatchAndLabelPreview();
    }

    public function updatedSearch(): void
    {
        $this->selectedParticipantId = null;
        $this->selectedParticipant = null;
    }

    public function selectParticipant(int $participantId): void
    {
        $participant = peserta::findOrFail($participantId);
        $this->selectedParticipantId = $participant->id;
        $this->selectedParticipant = $participant;
    }

    public function downloadPng()
    {
        $participant = $this->requireSelectedParticipant();
        $content = app(QRService::class)->generatePng((string) $participant->attendance_code);

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $participant->participant_number.'.png', [
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

        $this->batchPreview = $summary;
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
        $this->labelPreview = $participants->take(10)->values();

        if ($participant = $this->labelPreview->first()) {
            $this->labelPreviewHtml = app(PrintEngine::class)->label4x4($participant);
        } else {
            $this->labelPreviewHtml = '';
        }
    }

    public function render()
    {
        return view('livewire.qr-label.index', [
            'results' => $this->participantSearchResults(),
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

    private function requireSelectedParticipant(): peserta
    {
        if (! $this->selectedParticipantId) {
            abort(404);
        }

        return peserta::findOrFail($this->selectedParticipantId);
    }
}
