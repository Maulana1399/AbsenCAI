<?php

use App\Support\EventRolePermissionDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill: isi code untuk role lama berdasarkan mapping nama (legacy),
     * lalu isi permissions dari code untuk role yang sudah punya code dikenal.
     */
    public function up(): void
    {
        // 1. Isi code yang kosong berdasarkan mapping nama legacy.
        DB::table('event_roles')
            ->whereNull('code')
            ->orderBy('id')
            ->each(function ($role) {
                $suggested = EventRolePermissionDefaults::suggestCodeFromName($role->name);

                if ($suggested === null) {
                    return;
                }

                // Hindari konflik unique (event_id, code): jangan timpa jika
                // role lain di event yang sama sudah memakai code tersebut.
                $exists = DB::table('event_roles')
                    ->where('event_id', $role->event_id)
                    ->where('code', $suggested)
                    ->where('id', '!=', $role->id)
                    ->exists();

                if ($exists) {
                    return;
                }

                DB::table('event_roles')
                    ->where('id', $role->id)
                    ->update(['code' => $suggested]);
            });

        // 2. Isi permissions yang kosong dari code yang dikenal.
        DB::table('event_roles')
            ->orderBy('id')
            ->each(function ($role) {
                if (! EventRolePermissionDefaults::isKnownCode($role->code)) {
                    return;
                }

                $permissions = $role->permissions === null
                    ? []
                    : json_decode($role->permissions, true);

                if ($permissions !== []) {
                    return;
                }

                DB::table('event_roles')
                    ->where('id', $role->id)
                    ->update([
                        'permissions' => json_encode(EventRolePermissionDefaults::forCode($role->code)),
                    ]);
            });
    }

    public function down(): void
    {
        // Tidak ada operasi rollback yang aman untuk backfill code/permissions.
    }
};
