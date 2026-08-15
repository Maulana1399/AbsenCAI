<?php

namespace App\Support;

use App\Exceptions\UnknownEventRoleCodeException;

class EventRolePermissionDefaults
{
    public const EVENT_ABILITIES = [
        'view-dashboard',
        'manage-registration',
        'manage-participants',
        'manage-attendance',
        'manage-sessions',
        'manage-qr-labels',
        'manage-secretariat',
        'manage-import',
        'view-reports',
        'manage-pengajian',
        'view-activity-log',
        'manage-matches',
        'manage-officials',
        'submit-result',
    ];

    private const BY_CODE = [
        'super_admin' => self::EVENT_ABILITIES,
        'admin_event' => self::EVENT_ABILITIES,
        'ketua_event' => [
            'view-dashboard',
            'manage-registration',
            'manage-participants',
            'manage-attendance',
            'manage-sessions',
            'manage-secretariat',
            'view-reports',
        ],
        'sekretariat' => [
            'view-dashboard',
            'manage-registration',
            'manage-participants',
            'manage-attendance',
            'manage-sessions',
            'manage-qr-labels',
            'manage-secretariat',
            'manage-import',
            'view-reports',
            'manage-pengajian',
            'view-activity-log',
        ],
        'operator_registrasi' => ['manage-registration'],
        'operator_scan' => ['manage-attendance'],
        'operator_lapangan' => ['manage-attendance'],
        'pj_divisi' => ['view-dashboard', 'manage-attendance'],
        'viewer' => ['view-dashboard', 'view-reports'],
        'juri' => ['submit-result'],
        'ketua_fosda' => ['view-dashboard', 'manage-pengajian', 'view-reports'],
        'event_chair' => [
            'view-dashboard',
            'manage-registration',
            'manage-participants',
            'manage-attendance',
            'manage-sessions',
            'manage-secretariat',
            'view-reports',
        ],
        'guest' => ['view-dashboard'],
    ];

    /**
     * Legacy mapping nama role → code. Hanya dipakai untuk backfill migrasi
     * dan audit data lama — BUKAN sumber permission runtime.
     */
    private const CODE_BY_NAME = [
        'super admin' => 'super_admin',
        'admin event' => 'admin_event',
        'fosda' => 'ketua_fosda',
        'ketua event' => 'ketua_event',
        'ketua' => 'ketua_event',
        'sekretariat' => 'sekretariat',
        'sekretaris' => 'sekretariat',
        'registrasi' => 'operator_registrasi',
        'operator lapangan' => 'operator_lapangan',
        'lapangan' => 'operator_scan',
        'scan' => 'operator_scan',
        'divisi' => 'pj_divisi',
        'viewer' => 'viewer',
        'pengamat' => 'viewer',
        'juri' => 'juri',
        'event chair' => 'event_chair',
        'guest' => 'guest',
    ];

    public static function knownCodes(): array
    {
        return array_keys(self::BY_CODE);
    }

    public static function isKnownCode(?string $code): bool
    {
        return $code !== null && isset(self::BY_CODE[$code]);
    }

    /**
     * Ambil permission berdasarkan code. Non-throwing — mengembalikan []
     * untuk code yang tidak dikenal. Dipakai untuk audit & backfill.
     */
    public static function forCode(?string $code): array
    {
        if (! self::isKnownCode($code)) {
            return [];
        }

        return self::BY_CODE[$code];
    }

    /**
     * Resolve permission HANYA dari code.
     *
     * @throws UnknownEventRoleCodeException bila code kosong / tidak dikenal.
     */
    public static function resolve(?string $code): array
    {
        if ($code === null || trim($code) === '') {
            throw UnknownEventRoleCodeException::missingCode();
        }

        if (! self::isKnownCode($code)) {
            throw UnknownEventRoleCodeException::unknownCode($code);
        }

        return self::BY_CODE[$code];
    }

    /**
     * Legasi: tebak code dari nama role. Hanya untuk backfill migrasi &
     * audit data lama — BUKAN sumber permission runtime.
     */
    public static function suggestCodeFromName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $normalized = strtolower(trim($name));

        foreach (self::CODE_BY_NAME as $keyword => $code) {
            if (str_contains($normalized, $keyword)) {
                return $code;
            }
        }

        return null;
    }
}
