<?php

namespace App\Services\Import\Support;

use App\Services\Import\DTO\ImportContext;

final readonly class ImportVersion
{
    public function __construct(
        public string $current,
        public string $minimum,
        public string $supported,
    ) {
    }

    public function currentVersion(): string
    {
        return $this->current;
    }

    public function minimumVersion(): string
    {
        return $this->minimum;
    }

    public function supportedVersion(): string
    {
        return $this->supported;
    }

    public function accepts(string $version): bool
    {
        return version_compare($version, $this->minimum, '>=') && version_compare($version, $this->current, '<=');
    }
}
