<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;

class UserSetRole extends Command
{
    protected $signature = 'user:set-role {email : Email user yang akan diubah role-nya} {role : Role yang akan ditetapkan}';

    protected $description = 'Menetapkan role untuk user berdasarkan email';

    public function handle(): int
    {
        $email = $this->argument('email');
        $roleValue = $this->argument('role');

        $user = User::where('email', $email)->first();

        if ($user === null) {
            $this->error("User dengan email '{$email}' tidak ditemukan.");

            return Command::FAILURE;
        }

        $role = Role::tryFrom($roleValue);

        if ($role === null) {
            $validRoles = implode(', ', Role::values());
            $this->error("Role '{$roleValue}' tidak valid. Role yang tersedia: {$validRoles}");

            return Command::FAILURE;
        }

        $user->update(['role' => $role]);

        $this->info("Role user {$user->name} ({$user->email}) berhasil ditetapkan sebagai: {$role->label()}");

        return Command::SUCCESS;
    }
}
