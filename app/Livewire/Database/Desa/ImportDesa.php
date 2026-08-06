<?php

namespace App\Livewire\Database\Desa;

use App\Services\Import\Adapters\ImportAdapter;
use App\Services\Import\DTO\ImportError;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportPipelineResult;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportDesa extends Component
{
    use WithFileUploads;

    public $file = null;

    public bool $processing = false;

    public int $step = 1;

    public array $previewRows = [];

    public array $validationErrors = [];

    public array $summary = [];

    public array $importResult = [];

    public ?string $uploadError = null;

    protected function rules(): array
    {
        return [
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
        ];
    }

    protected $messages = [
        'file.required' => 'Pilih file CSV atau Excel untuk diimport.',
        'file.file' => 'File harus berupa file yang valid.',
        'file.mimes' => 'File harus berformat CSV, TXT, XLSX, atau XLS.',
        'file.max' => 'Ukuran file maksimal 5MB.',
    ];

    public function updatedFile(): void
    {
        $this->resetErrorBag('file');
        $this->uploadError = null;
        $this->previewRows = [];
        $this->validationErrors = [];
        $this->summary = [];
        $this->importResult = [];
        $this->step = 1;
    }

    public function uploadError(): void
    {
        $this->uploadError = 'Upload file gagal. Periksa ukuran file (maks 5MB) dan format yang didukung (CSV, XLSX, XLS, TXT).';
    }

    public function preview(): void
    {
        Gate::authorize('manage-master-data');

        $this->validate();
        $this->processing = true;
        $this->previewRows = [];
        $this->validationErrors = [];
        $this->importResult = [];

        try {
            $result = app(ImportAdapter::class)->preview('desa', $this->file);

            $this->previewRows = $this->extractPreviewRows($result);
            $this->validationErrors = $this->extractValidationErrors($result);
            $this->summary = $this->summarize($result);
            $this->step = 2;
        } catch (\Throwable $e) {
            $this->validationErrors = [
                ['row' => 0, 'errors' => ['Gagal membaca file: '.$e->getMessage()]],
            ];
            $this->step = 2;
        } finally {
            $this->processing = false;
        }
    }

    public function executeImport(): void
    {
        Gate::authorize('manage-master-data');

        if ($this->processing || empty($this->previewRows) || ! empty($this->validationErrors)) {
            return;
        }

        $this->processing = true;

        try {
            $result = app(ImportAdapter::class)->commit('desa', $this->file);

            $this->importResult = $this->extractResult($result);
            $this->step = 3;
            $this->dispatch('refreshDesa');
        } catch (\Throwable $e) {
            $this->importResult = ['error' => 'Gagal menjalankan import: '.$e->getMessage()];
            $this->step = 3;
        } finally {
            $this->processing = false;
        }
    }

    public function resetImport(): void
    {
        $this->step = 1;
        $this->file = null;
        $this->previewRows = [];
        $this->validationErrors = [];
        $this->summary = [];
        $this->importResult = [];
        $this->uploadError = null;
        $this->resetErrorBag('file');
    }

    public function render()
    {
        return view('livewire.database.desa.import-desa');
    }

    private function extractPreviewRows(ImportPipelineResult $result): array
    {
        return array_map(
            fn (NormalizedImportRow $row) => $row->data,
            $result->rows,
        );
    }

    private function extractValidationErrors(ImportPipelineResult $result): array
    {
        $errors = [];

        foreach ($result->summary?->errors ?? [] as $error) {
            $errors[] = [
                'row' => $error instanceof ImportError ? $error->rowNumber : ($error['row'] ?? 0),
                'errors' => [$error instanceof ImportError ? $error->message : ($error['message'] ?? 'Baris tidak valid.')],
            ];
        }

        return $errors;
    }

    private function summarize(ImportPipelineResult $result): array
    {
        $summary = $result->summary;

        return [
            'total' => $summary?->totalRows ?? 0,
            'valid' => $summary?->validRows ?? 0,
            'invalid' => $summary?->invalidRows ?? 0,
            'duplicate' => $summary?->duplicateRows ?? 0,
            'will_create' => $summary?->validRows ?? 0,
        ];
    }

    private function extractResult(ImportPipelineResult $result): array
    {
        $summary = $result->summary;

        return [
            'total' => $summary?->totalRows ?? 0,
            'created' => $summary?->createdRows ?? 0,
            'duplicate' => $summary?->skippedRows ?? 0,
            'failed' => count($result->commit?->failedRows ?? []),
            'warning' => count($summary?->warnings ?? []),
            'errors' => array_map(
                fn ($row) => [
                    'row' => $row instanceof ImportError ? $row->rowNumber : ($row['row'] ?? 0),
                    'message' => $row instanceof ImportError ? $row->message : ($row['message'] ?? ''),
                ],
                $result->commit?->failedRows ?? [],
            ),
        ];
    }
}
