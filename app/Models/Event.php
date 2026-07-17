<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Event extends Model
{
    protected $fillable = [
        'name',
        'slug',
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

    public function participations()
    {
        return $this->hasMany(Participation::class);
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

    protected static function booted(): void
    {
        static::creating(function (Event $event) {
            if (blank($event->slug)) {
                $event->slug = Str::slug($event->name);
            }
        });
    }
}
