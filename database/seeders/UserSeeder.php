<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Event Admin', 'email' => 'event@kja.local', 'role' => Role::Admin],
            ['name' => 'Operator Lapangan', 'email' => 'lapangan@kja.local', 'role' => Role::OperatorScan],
            ['name' => 'Operator Registrasi', 'email' => 'registrasi@kja.local', 'role' => Role::OperatorRegistrasi],
            ['name' => 'Viewer', 'email' => 'viewer@kja.local', 'role' => Role::Viewer],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make('admin123'),
                    'email_verified_at' => now(),
                    'role' => $u['role'],
                    'person_id' => null,
                ]
            );
        }
    }
}
