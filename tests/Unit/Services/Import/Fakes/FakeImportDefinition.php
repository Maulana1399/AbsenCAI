<?php

namespace Tests\Unit\Services\Import\Fakes;

use App\Services\Import\Contracts\ImportActivityLogger;
use App\Services\Import\Contracts\ImportCommitter;
use App\Services\Import\Contracts\ImportDefinition;
use App\Services\Import\Contracts\ImportDuplicateDetector;
use App\Services\Import\Contracts\ImportNormalizer;
use App\Services\Import\Contracts\ImportParser;
use App\Services\Import\Contracts\ImportValidator;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\NullObjects\NullImportActivityLogger;
use App\Services\Import\NullObjects\NullImportDuplicateDetector;
use App\Services\Import\NullObjects\NullImportNormalizer;
use App\Services\Import\NullObjects\NullImportParser;
use App\Services\Import\NullObjects\NullImportValidator;

class FakeImportDefinition implements ImportDefinition
{
    public function __construct(
        private readonly ?ImportParser $parser = null,
        private readonly ?ImportValidator $validator = null,
        private readonly ?ImportNormalizer $normalizer = null,
        private readonly ?ImportDuplicateDetector $duplicateDetector = null,
        private readonly ?ImportCommitter $committer = null,
        private readonly ?ImportActivityLogger $activityLogger = null,
        private readonly string $keyValue = 'fake',
        private readonly string $labelValue = 'Fake Import',
        private readonly string $current = '2.0.0',
        private readonly string $minimum = '1.0.0',
        private readonly string $supported = '1.0.0',
        private readonly bool $preview = true,
        private readonly bool $commit = true,
    ) {}

    public function key(): string
    {
        return $this->keyValue;
    }

    public function label(): string
    {
        return $this->labelValue;
    }

    public function parser(): ImportParser
    {
        return $this->parser ?? new NullImportParser;
    }

    public function validator(): ImportValidator
    {
        return $this->validator ?? new NullImportValidator;
    }

    public function normalizer(): ImportNormalizer
    {
        return $this->normalizer ?? new NullImportNormalizer;
    }

    public function duplicateDetector(): ImportDuplicateDetector
    {
        return $this->duplicateDetector ?? new NullImportDuplicateDetector;
    }

    public function committer(): ImportCommitter
    {
        return $this->committer ?? throw new \LogicException('No committer bound on FakeImportDefinition.');
    }

    public function activityLogger(): ImportActivityLogger
    {
        return $this->activityLogger ?? new NullImportActivityLogger;
    }

    public function supportedVersion(): string
    {
        return $this->supported;
    }

    public function minimumVersion(): string
    {
        return $this->minimum;
    }

    public function currentVersion(): string
    {
        return $this->current;
    }

    public function supportsPreview(ImportContext $context): bool
    {
        return $this->preview;
    }

    public function supportsCommit(ImportContext $context): bool
    {
        return $this->commit;
    }
}
