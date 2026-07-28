<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitionAnnouncement extends Model
{
    protected $fillable = [
        'event_id',
        'message',
        'is_active',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
