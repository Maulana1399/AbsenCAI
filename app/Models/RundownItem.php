<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RundownItem extends Model
{
    protected $fillable = [
        'event_id',
        'rundown_id',
        'activity_id',
        'venue_id',
        'starts_at',
        'ends_at',
        'sequence',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function rundown()
    {
        return $this->belongsTo(Rundown::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }
}
