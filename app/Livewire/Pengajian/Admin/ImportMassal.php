<?php

namespace App\Livewire\Pengajian\Admin;

use App\Livewire\Import\ImportWizardBase;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\ImportError;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportPipelineResult;
use App\Support\ActiveEventContext;

/**
 * Pengajian Import Massal wizard.
 *
 * Extends the reusable ImportWizardBase (lifecycle, upload, reset, loading,
 * navigation all inherited) but keeps the golden 3-step UI and result format
 * exactly as before via overrides. All import work flows through the Import
 * Framework (ImportAdapter → PengajianImportDefinition → Pipeline).
 */
class ImportMassal extends ImportWizardBase
{
    public bool $noActiveEvent = false;

    public ?string $eventName = null;

    protected function definitionKey(): string
    {
        return 'pengajian';
    }

    protected function gateAbility(): string
    {
        return 'manage-pengajian';
    }

    protected function steps(): array
    {
        return ['Upload', 'Preview & Validasi', 'Hasil'];
    }

    protected function refreshEvent(): ?string
    {
        return null;
    }

    public function mount(): void
    {
        $event = app(ActiveEventContext::class)->current();

        if ($event === null) {
            $this->noActiveEvent = true;

            return;
        }

        $this->eventName = $event->name;
    }

    protected function importContext(string $mode): ?ImportContext
    {
        $event = app(ActiveEventContext::class)->requireCurrent();

        return new ImportContext(
            type: 'pengajian',
            eventId: $event->id,
            userId: auth()->id(),
            fileName: $this->file?->getClientOriginalName(),
            source: 'livewire',
            mode: $mode,
            options: ['file' => $this->file],
            definitionKey: 'pengajian',
        );
    }

    protected function emptyFileMessage(): string
    {
        return 'File tidak berisi data. Pastikan file memiliki minimal satu baris data (di bawah header).';
    }

    protected function stepAfterPreview(bool $hasErrors): int
    {
        return 2;
    }

    protected function stepAfterParseError(): int
    {
        return 2;
    }

    protected function resultStep(): int
    {
        return 3;
    }

    protected function resultErrorStep(): int
    {
        return 3;
    }

    protected function parseErrorMessage(\Throwable $e): string
    {
        $message = $e->getPrevious()?->getMessage() ?? $e->getMessage();

        return 'Gagal membaca file: '.$message;
    }

    protected function extractPreviewRows(ImportPipelineResult $result): array
    {
        return array_map(
            fn (NormalizedImportRow $row) => $row->data,
            $result->rows,
        );
    }

    protected function extractValidationErrors(ImportPipelineResult $result): array
    {
        $grouped = [];

        foreach ($result->summary?->errors ?? [] as $error) {
            $row = $error instanceof ImportError ? $error->rowNumber : ($error['row'] ?? 0);
            $message = $error instanceof ImportError ? $error->message : ($error['message'] ?? '');

            $grouped[$row]['row'] = $row;
            $grouped[$row]['errors'][] = $message;
        }

        return array_values($grouped);
    }

    protected function extractResult(ImportPipelineResult $result): array
    {
        $commit = $result->commit;
        $metrics = $commit?->metrics ?? [];

        $errors = array_map(
            fn ($row) => [
                'row' => $row['row'] ?? 0,
                'message' => $row['message'] ?? '',
            ],
            $commit?->failedRows ?? [],
        );

        return [
            'created_persons' => $metrics['created_persons'] ?? 0,
            'matched_persons' => $metrics['matched_persons'] ?? 0,
            'created_participations' => $metrics['created_participations'] ?? 0,
            'skipped_duplicates' => $metrics['skipped_duplicates'] ?? 0,
            'failed_rows' => $metrics['failed_rows'] ?? count($errors),
            'errors' => $errors,
        ];
    }

    public function render()
    {
        return view('livewire.pengajian.admin.import-massal');
    }
}
