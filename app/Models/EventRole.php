<?php

namespace App\Models;

use App\Exceptions\UnknownEventRoleCodeException;
use App\Support\EventRolePermissionDefaults;
use Illuminate\Database\Eloquent\Model;

class EventRole extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'code',
        'scope',
        'permissions',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'permissions' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EventRole $role) {
            if (blank($role->code) || ! EventRolePermissionDefaults::isKnownCode($role->code)) {
                throw UnknownEventRoleCodeException::unknownCode($role->code);
            }

            if (($role->permissions ?? []) === []) {
                $role->permissions = EventRolePermissionDefaults::forCode($role->code);
            }
        });
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function committeeAssignments()
    {
        return $this->hasMany(EventCommitteeAssignment::class);
    }
}
