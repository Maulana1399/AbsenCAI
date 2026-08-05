<?php

namespace App\Services\User;

use App\Enums\Role;
use App\Models\SuratIzin;
use App\Models\User;
use App\Services\Audit\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function create(array $data): User
    {
        $role = Role::tryFrom($data['role']);

        if ($role === null) {
            throw ValidationException::withMessages([
                'role' => 'Role tidak valid.',
            ]);
        }

        if (isset($data['person_id']) && $data['person_id'] !== null) {
            $existingUser = User::where('person_id', $data['person_id'])->first();
            if ($existingUser !== null) {
                throw ValidationException::withMessages([
                    'person_id' => 'Person ini sudah terhubung dengan user lain.',
                ]);
            }
        }

        return DB::transaction(function () use ($data, $role) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $role,
                'person_id' => $data['person_id'] ?? null,
            ]);

            $props = [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $role->value,
            ];

            if (isset($data['person_id'])) {
                $props['person_id'] = $data['person_id'];
            }

            $this->activityLogService->log(
                action: 'created',
                module: 'user',
                description: 'Akun user dibuat: '.$user->email,
                subject: $user,
                properties: $props,
            );

            return $user;
        });
    }

    public function update(User $user, array $data): User
    {
        $role = Role::tryFrom($data['role']);

        if ($role === null) {
            throw ValidationException::withMessages([
                'role' => 'Role tidak valid.',
            ]);
        }

        $originalRole = $user->role;

        if ($originalRole === Role::SuperAdmin && $role !== Role::SuperAdmin) {
            $this->ensureNotLastSuperAdmin($user);
        }

        return DB::transaction(function () use ($user, $data, $role, $originalRole) {
            $updateData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $role,
            ];

            if (array_key_exists('person_id', $data)) {
                $newPersonId = $data['person_id'];

                if ($newPersonId !== $user->person_id) {
                    if ($newPersonId !== null) {
                        $existingUser = User::where('person_id', $newPersonId)
                            ->where('id', '!=', $user->id)
                            ->first();
                        if ($existingUser !== null) {
                            throw ValidationException::withMessages([
                                'person_id' => 'Person ini sudah terhubung dengan user lain.',
                            ]);
                        }
                    }

                    $updateData['person_id'] = $newPersonId;

                    $this->activityLogService->log(
                        action: 'person_link_changed',
                        module: 'user',
                        description: 'Person link user '.$user->email.' diubah',
                        subject: $user,
                        properties: [
                            'user_id' => $user->id,
                            'old_person_id' => $user->person_id,
                            'new_person_id' => $newPersonId,
                        ],
                    );
                }
            }

            $user->update($updateData);

            if ($originalRole !== $role) {
                $this->activityLogService->log(
                    action: 'role_changed',
                    module: 'user',
                    description: 'Role user '.$user->email.' diubah dari '.($originalRole?->value ?? 'null').' ke '.$role->value,
                    subject: $user,
                    properties: [
                        'user_id' => $user->id,
                        'old_role' => $originalRole?->value,
                        'new_role' => $role->value,
                    ],
                );
            }

            $this->activityLogService->log(
                action: 'updated',
                module: 'user',
                description: 'Data user diperbarui: '.$user->email,
                subject: $user,
                properties: [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'role' => $role->value,
                ],
            );

            return $user->fresh();
        });
    }

    public function resetPassword(User $user, string $newPassword): void
    {
        DB::transaction(function () use ($user, $newPassword) {
            $user->update([
                'password' => Hash::make($newPassword),
            ]);

            $this->activityLogService->log(
                action: 'password_reset',
                module: 'user',
                description: 'Password user direset: '.$user->email,
                subject: $user,
                properties: [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ],
            );
        });
    }

    public function delete(User $user, User $currentUser): void
    {
        if ($user->id === $currentUser->id) {
            throw ValidationException::withMessages([
                'user' => 'Anda tidak dapat menghapus akun Anda sendiri.',
            ]);
        }

        if ($user->role === Role::SuperAdmin) {
            $this->ensureNotLastSuperAdmin($user);
        }

        $hasSuratIzin = SuratIzin::where('created_by', $user->id)->exists();

        if ($hasSuratIzin) {
            throw ValidationException::withMessages([
                'user' => 'User ini memiliki data surat izin dan tidak dapat dihapus.',
            ]);
        }

        DB::transaction(function () use ($user) {
            $email = $user->email;

            $this->activityLogService->log(
                action: 'deleted',
                module: 'user',
                description: 'Akun user dihapus: '.$email,
                properties: [
                    'email' => $email,
                ],
            );

            $user->delete();
        });
    }

    private function ensureNotLastSuperAdmin(User $user): void
    {
        $superAdminCount = User::where('role', Role::SuperAdmin)->count();

        if ($superAdminCount <= 1 && $user->role === Role::SuperAdmin) {
            throw ValidationException::withMessages([
                'role' => 'Tidak dapat menghapus atau mengubah role Super Admin terakhir.',
            ]);
        }
    }

    public function canDelete(User $user): array
    {
        $reasons = [];

        if ($user->role === Role::SuperAdmin) {
            $superAdminCount = User::where('role', Role::SuperAdmin)->count();

            if ($superAdminCount <= 1) {
                $reasons[] = 'User ini adalah Super Admin terakhir.';
            }
        }

        if (SuratIzin::where('created_by', $user->id)->exists()) {
            $reasons[] = 'User ini memiliki data surat izin.';
        }

        return $reasons;
    }

    public function canChangeRole(User $user): array
    {
        $reasons = [];

        if ($user->role === Role::SuperAdmin) {
            $superAdminCount = User::where('role', Role::SuperAdmin)->count();

            if ($superAdminCount <= 1) {
                $reasons[] = 'User ini adalah Super Admin terakhir. Role tidak dapat diubah.';
            }
        }

        return $reasons;
    }
}
