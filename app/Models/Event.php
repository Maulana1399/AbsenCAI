<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Event extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'event_type',
        'description',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function isCai(): bool
    {
        return $this->event_type === 'cai';
    }

    public function isPengajian(): bool
    {
        return $this->event_type === 'pengajian';
    }

    public function scopeCai($query)
    {
        return $query->where('event_type', 'cai');
    }

    public function scopePengajian($query)
    {
        return $query->where('event_type', 'pengajian');
    }

    public function participations()
    {
        return $this->hasMany(Participation::class);
    }

    public function activityGroups()
    {
        return $this->hasMany(ActivityGroup::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function activityRegistrations()
    {
        return $this->hasMany(ActivityRegistration::class);
    }

    public function sesiAbsensis()
    {
        return $this->hasMany(SesiAbsensi::class);
    }

    public function people()
    {
        return $this->belongsToMany(Person::class, 'participations');
    }

    public function legacyPesertaMappings()
    {
        return $this->hasMany(LegacyPesertaMapping::class, 'event_id');
    }

    public function eventRoles()
    {
        return $this->hasMany(EventRole::class);
    }

    public function committeeAssignments()
    {
        return $this->hasMany(EventCommitteeAssignment::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Event $event) {
            if (blank($event->slug)) {
                $event->slug = Str::slug($event->name);
            }
        });
    }
}
