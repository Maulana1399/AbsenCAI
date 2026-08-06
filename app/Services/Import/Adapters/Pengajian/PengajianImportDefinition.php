<?php

namespace App\Services\Import\Adapters\Pengajian;

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
use App\Services\Import\Template\PengajianImportTemplate;

/**
 * Pengajian import definition (IF-08). The golden implementation now runs on
 * the Import Framework; every behavior (parse, validation messages, commit
 * counters, writes) is preserved exactly.
 */
final class PengajianImportDefinition implements ImportDefinition, ImportDefinitionMetadata
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
        return 'pengajian';
    }

    public function label(): string
    {
        return 'Import Massal Pengajian';
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
        return 'Import Massal Peserta Pengajian';
    }

    public function description(): string
    {
        return 'Import peserta Pengajian Desa secara massal dari file CSV atau Excel.';
    }

    public function icon(): string
    {
        return 'arrow-down-tray';
    }

    public function parameters(): array
    {
        return [];
    }

    public function parameterOptions(string $key, ImportContext $context): array
    {
        return [];
    }

    public function columns(): array
    {
        return [
            'nama' => ['label' => 'Nama', 'required' => true, 'example' => 'Ahmad Wijaya'],
            'jenis_kelamin' => ['label' => 'Jenis Kelamin', 'required' => true, 'example' => 'L'],
            'tanggal_lahir' => ['label' => 'Tanggal Lahir', 'required' => true, 'example' => '2000-01-15'],
            'desa' => ['label' => 'Desa', 'required' => true, 'example' => 'Desa Contoh'],
            'kelompok' => ['label' => 'Kelompok', 'required' => false, 'example' => 'Kelompok A'],
        ];
    }

    public function rules(): array
    {
        return [
            'nama' => 'required|string',
            'jenis_kelamin' => 'required|in:L,P',
            'tanggal_lahir' => 'required|date',
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

        if (empty(trim($row['nama'] ?? ''))) {
            $errors[] = 'Nama wajib diisi.';
        }

        if (empty($row['jenis_kelamin'] ?? '') || ! in_array(strtoupper($row['jenis_kelamin']), ['L', 'P'])) {
            $errors[] = 'Jenis kelamin harus L atau P.';
        }

        if (empty($row['tanggal_lahir'] ?? '')) {
            $errors[] = 'Tanggal lahir wajib diisi.';
        }

        if (empty(trim($row['desa'] ?? ''))) {
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
            warnings: $validation->warnings,
        );
    }

    public function template(): ImportTemplate
    {
        return new PengajianImportTemplate;
    }
}
