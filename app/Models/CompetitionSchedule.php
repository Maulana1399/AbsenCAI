<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitionSchedule extends Model
{
    protected $fillable = [
        'competition_class_id',
        'venue_id',
        'start_at',
        'end_at',
        'status',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function competitionClass()
    {
        return $this->belongsTo(CompetitionClass::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function scheduleEntries()
    {
        return $this->hasMany(CompetitionScheduleEntry::class);
    }
}
