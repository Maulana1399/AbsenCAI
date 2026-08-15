<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case KetuaEvent = 'ketua_event';
    case Sekretariat = 'sekretariat';
    case PjDivisi = 'pj_divisi';
    case OperatorRegistrasi = 'operator_registrasi';
    case OperatorScan = 'operator_scan';
    case Juri = 'juri';
    case Viewer = 'viewer';
    case EventChair = 'event_chair';
    case Guest = 'guest';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::KetuaEvent => 'Ketua Event',
            self::Sekretariat => 'Sekretariat',
            self::PjDivisi => 'PJ Divisi',
            self::OperatorRegistrasi => 'Operator Registrasi',
            self::OperatorScan => 'Operator Scan',
            self::Juri => 'Juri',
            self::Viewer => 'Viewer',
            self::EventChair => 'Event Chair',
            self::Guest => 'Guest',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Role platform global (Super Admin / Admin) — satu-satunya role yang
     * boleh dikelola di User Management. Role event berasal dari EventRole.
     */
    public static function platformCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $role) => $role->isPlatformRole(),
        ));
    }

    public static function platformValues(): array
    {
        return array_map(fn (self $role) => $role->value, self::platformCases());
    }

    /**
     * Role akun yang dapat dipilih pada User Management: platform (Super Admin /
     * Admin) + role event-scoped (Event Chair / Guest). Event Chair dan Guest
     * TIDAK mendapat akses global — akses event mereka datang dari Event
     * Membership (event_committee_assignments.user_id).
     */
    public static function accountCases(): array
    {
        return [
            self::SuperAdmin,
            self::Admin,
            self::EventChair,
            self::Guest,
        ];
    }

    public static function accountValues(): array
    {
        return array_map(fn (self $role) => $role->value, self::accountCases());
    }

    public function isPlatformRole(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin], true);
    }

    public function isAccountRole(): bool
    {
        return in_array($this, self::accountCases(), true);
    }
}
