<?php

namespace App\Services\Import\Adapters\Participation;

use App\Models\Event;
use App\Services\Import\Contracts\ImportActivityLogger;
use App\Services\Import\Contracts\ImportCommitter;
use App\Services\Import\Contracts\ImportDefinition;
use App\Services\Import\Contracts\ImportDefinitionMetadata;
use App\Services\Import\Contracts\ImportDuplicateDetector;
use App\Services\Import\Contracts\ImportNormalizer;
use App\Services\Import\Contracts\ImportParser;
use App\Services\Import\Contracts\ImportTemplate;
use App\Services\Import\Contracts\ImportValidator;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportPreview;
use App\Services\Import\Results\ImportSummary;
use App\Services\Import\Template\ParticipationImportTemplateExport;

/**
 * Participation import definition (IF-07) — Design C link Person → Event.
 *
 * Person is looked up first (never blindly created); duplicate = existing
 * Participation for the resolved Person in the target event. Commit delegates
 * to the canonical ManualParticipantRegistrationService.
 */
final class ParticipationImportDefinition implements ImportDefinition, ImportDefinitionMetadata
{
    public function __construct(
        private readonly ImportParser $parser,
        private readonly ImportValidator $validator,
        private readonly ImportNormalizer $normalizer,
        private readonly ImportDuplicateDetector $duplicateDetector,
        private readonly ImportCommitter $committer,
        private readonly ImportActivityLogger $activityLogger,
    ) {}

    // --- ImportDefinition contract (consumed by the pipeline) ---

    public function key(): string
    {
        return 'participation';
    }

    public function label(): string
    {
        return 'Import Participation';
    }

    public function parser(): ImportParser
    {
        return $this->parser;
    }

    public function validator(): ImportValidator
    {
        return $this->validator;
    }

    public function normalizer(): ImportNormalizer
    {
        return $this->normalizer;
    }

    public function duplicateDetector(): ImportDuplicateDetector
    {
        return $this->duplicateDetector;
    }

    public function committer(): ImportCommitter
    {
        return $this->committer;
    }

    public function activityLogger(): ImportActivityLogger
    {
        return $this->activityLogger;
    }

    public function supportedVersion(): string
    {
        return '1.0.0';
    }

    public function minimumVersion(): string
    {
        return '1.0.0';
    }

    public function currentVersion(): string
    {
        return '1.0.0';
    }

    public function supportsPreview(ImportContext $context): bool
    {
        return true;
    }

    public function supportsCommit(ImportContext $context): bool
    {
        return true;
    }

    // --- Module capability API (used by the adapter / wizard) ---

    public function displayName(): string
    {
        return 'Import Participation';
    }

    public function description(): string
    {
        return 'Daftarkan Person ke sebuah Event. Person yang sudah ada akan dicari dan dipakai ulang; Partisipasi yang sudah ada akan dilewati.';
    }

    public function icon(): string
    {
        return 'clipboard-document-check';
    }

    public function parameters(): array
    {
        return [
            'event_id' => ['label' => 'Event', 'type' => 'select', 'required' => true],
        ];
    }

    public function parameterOptions(string $key, ImportContext $context): array
    {
        if ($key === 'event_id') {
            return Event::orderBy('name')->pluck('name', 'id')->all();
        }

        return [];
    }

    public function columns(): array
    {
        return [
            'nama' => ['label' => 'Nama', 'required' => true, 'example' => 'Ahmad Wijaya'],
            'jenis_kelamin' => ['label' => 'Jenis Kelamin', 'required' => true, 'example' => 'L'],
            'tanggal_lahir' => ['label' => 'Tanggal Lahir', 'required' => false, 'example' => '2000-01-15'],
            'desa' => ['label' => 'Desa', 'required' => true, 'example' => 'Desa Contoh'],
            'kelompok' => ['label' => 'Kelompok', 'required' => false, 'example' => 'Kelompok A'],
            'jenis_peserta' => ['label' => 'Jenis Peserta', 'required' => false, 'example' => 'Wajib'],
            'status_registrasi' => ['label' => 'Status Registrasi', 'required' => false, 'example' => ''],
            'regu' => ['label' => 'Regu', 'required' => false, 'example' => 'Regu A'],
        ];
    }

    public function rules(): array
    {
        return [
            'nama' => 'required|string',
            'jenis_kelamin' => 'required|in:L,P',
            'desa' => 'required|string',
        ];
    }

    /**
     * @param  array<int, \App\Services\Import\DTO\RawImportRow>  $rows
     * @return array<int, \App\Services\Import\DTO\NormalizedImportRow>
     */
    public function normalize(array $rows, ImportContext $context): array
    {
        return $this->normalizer->normalize($rows, $context);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, string>
     */
    public function validateRow(array $row, ImportContext $context): array
    {
        $errors = [];

        if (trim($row['nama'] ?? '') === '') {
            $errors[] = 'Nama wajib diisi.';
        }

        if (! in_array(trim($row['jenis_kelamin'] ?? ''), ['L', 'P'], true)) {
            $errors[] = 'Jenis kelamin harus Laki-laki (L) atau Perempuan (P).';
        }

        if (trim($row['desa'] ?? '') === '') {
            $errors[] = 'Desa wajib diisi.';
        }

        return $errors;
    }

    /**
     * @param  array<int, \App\Services\Import\DTO\NormalizedImportRow>  $rows
     */
    public function duplicate(array $rows, ImportContext $context): ImportSummary
    {
        return $this->duplicateDetector->detect($rows, $context);
    }

    /**
     * @param  array<int, \App\Services\Import\DTO\NormalizedImportRow>  $rows
     */
    public function preview(array $rows, ImportContext $context): ImportPreview
    {
        $summary = $this->validator->validate($rows, $context);

        $canCommit = $summary->invalidRows === 0 && count($rows) > 0;

        return new ImportPreview(
            context: $context,
            rows: $rows,
            summary: $summary,
            canCommit: $canCommit,
        );
    }

    /**
     * @param  array<int, \App\Services\Import\DTO\NormalizedImportRow>  $rows
     */
    public function commit(array $rows, ImportContext $context): ImportCommit
    {
        return $this->committer->commit($rows, $context);
    }

    /**
     * @param  array<int, \App\Services\Import\DTO\NormalizedImportRow>  $rows
     */
    public function summary(array $rows, ImportContext $context): ImportSummary
    {
        $validation = $this->validator->validate($rows, $context);
        $duplicate = $this->duplicateDetector->detect($rows, $context);

        return new ImportSummary(
            totalRows: $validation->totalRows,
            validRows: $validation->validRows,
            invalidRows: $validation->invalidRows,
            duplicateRows: $duplicate->duplicateRows,
            errors: $validation->errors,
            warnings: $duplicate->warnings,
        );
    }

    public function template(): ImportTemplate
    {
        return new ParticipationImportTemplateExport;
    }
}
