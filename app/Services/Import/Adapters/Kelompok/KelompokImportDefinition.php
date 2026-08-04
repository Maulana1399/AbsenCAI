<?php

namespace App\Services\Import\Adapters\Kelompok;

use App\Services\Import\Contracts\ImportActivityLogger;
use App\Services\Import\Contracts\ImportCommitter;
use App\Services\Import\Contracts\ImportDefinition;
use App\Services\Import\Contracts\ImportDuplicateDetector;
use App\Services\Import\Contracts\ImportNormalizer;
use App\Services\Import\Contracts\ImportParser;
use App\Services\Import\Contracts\ImportValidator;
use App\Services\Import\DTO\ImportContext;

final class KelompokImportDefinition implements ImportDefinition
{
    public function __construct(
        private readonly ImportParser $parser,
        private readonly ImportValidator $validator,
        private readonly ImportNormalizer $normalizer,
        private readonly ImportDuplicateDetector $duplicateDetector,
        private readonly ImportCommitter $committer,
        private readonly ImportActivityLogger $activityLogger,
    ) {
    }

    public function key(): string { return 'kelompok'; }
    public function label(): string { return 'Import Kelompok'; }
    public function parser(): ImportParser { return $this->parser; }
    public function validator(): ImportValidator { return $this->validator; }
    public function normalizer(): ImportNormalizer { return $this->normalizer; }
    public function duplicateDetector(): ImportDuplicateDetector { return $this->duplicateDetector; }
    public function committer(): ImportCommitter { return $this->committer; }
    public function activityLogger(): ImportActivityLogger { return $this->activityLogger; }
    public function supportedVersion(): string { return '1.0.0'; }
    public function minimumVersion(): string { return '1.0.0'; }
    public function currentVersion(): string { return '1.0.0'; }
    public function supportsPreview(ImportContext $context): bool { return true; }
    public function supportsCommit(ImportContext $context): bool { return true; }
}
