<?php

namespace App\Exceptions;

use App\Support\EventRolePermissionDefaults;
use RuntimeException;

class UnknownEventRoleCodeException extends RuntimeException
{
    public static function missingCode(): self
    {
        return new self('Event role code wajib diisi. Permission hanya di-resolve dari code sistem yang dikenal.');
    }

    public static function unknownCode(?string $code): self
    {
        $known = implode(', ', EventRolePermissionDefaults::knownCodes());

        return new self(sprintf(
            'Kode event role "%s" tidak dikenal. Gunakan salah satu kode sistem berikut: %s.',
            $code ?? '(kosong)',
            $known,
        ));
    }
}
