<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventAttendance extends Model
{
    protected $fillable = [
        'participation_id',
        'event_id',
        'desa_id',
        'attended_at',
        'method',
        'recorded_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'attended_at' => 'datetime',
            'metadata' => 'json',
        ];
    }

    public function participation()
    {
        return $this->belongsTo(Participation::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function desa()
    {
        return $this->belongsTo(desa::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
