<?php

namespace App\Console\Commands;

use App\Models\EventRole;
use App\Support\EventRolePermissionDefaults;
use Illuminate\Console\Command;

class AuditEventRoles extends Command
{
    protected $signature = 'event-roles:audit';

    protected $description = 'Deteksi event role bermasalah: tanpa code, tanpa permission, code tidak dikenal';

    public function handle(): int
    {
        $roles = EventRole::with('event')->orderBy('event_id')->orderBy('id')->get();

        $withoutCode = [];
        $withoutPermission = [];
        $unknownCode = [];

        foreach ($roles as $role) {
            $context = ($role->event->name ?? "#{$role->event_id}") . " / #{$role->id} {$role->name}";

            if (blank($role->code)) {
                $suggested = EventRolePermissionDefaults::suggestCodeFromName($role->name);
                $withoutCode[] = [$context, $role->name, $suggested ?? '(?)'];
            }

            if (empty($role->permissions)) {
                $withoutPermission[] = [$context, $role->name, $role->code ?? '(kosong)'];
            }

            if (! blank($role->code) && ! EventRolePermissionDefaults::isKnownCode($role->code)) {
                $unknownCode[] = [$context, $role->name, $role->code];
            }
        }

        $this->newLine();
        $this->line('=== Audit Event Role ===');

        if ($roles->isEmpty()) {
            $this->info('Tidak ada event role di database.');

            return Command::SUCCESS;
        }

        $total = count($withoutCode) + count($withoutPermission) + count($unknownCode);
        $this->line("Total event role: {$roles->count()}");

        if (count($withoutCode) > 0) {
            $this->newLine();
            $this->warn('ROLE TANPA CODE (' . count($withoutCode) . '):');
            $this->table(['Event / Role', 'Nama', 'Code saran (dari nama)'], $withoutCode);
        }

        if (count($unknownCode) > 0) {
            $this->newLine();
            $this->warn('CODE TIDAK DIKENAL (' . count($unknownCode) . '):');
            $this->table(['Event / Role', 'Nama', 'Code'], $unknownCode);
        }

        if (count($withoutPermission) > 0) {
            $this->newLine();
            $this->warn('ROLE TANPA PERMISSION (' . count($withoutPermission) . '):');
            $this->table(['Event / Role', 'Nama', 'Code'], $withoutPermission);
        }

        $this->newLine();

        if ($total === 0) {
            $this->info('Semua event role valid.');

            return Command::SUCCESS;
        }

        $this->error("Ditemukan {$total} masalah pada event role.");

        return Command::FAILURE;
    }
}
