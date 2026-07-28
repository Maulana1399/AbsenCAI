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
        'required_participants',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'required_participants' => 'integer',
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

    public function isReadyForStart(): bool
    {
        if ($this->status !== 'Ready') {
            return false;
        }
        return $this->scheduleEntries()->count() >= $this->required_participants;
    }

    public function canAutoReady(): bool
    {
        if ($this->status !== 'Scheduled') {
            return false;
        }
        return $this->scheduleEntries()->count() >= $this->required_participants;
    }

    public function scopeWherePlaying($query)
    {
        return $query->where('status', 'Playing');
    }

    public function scopeWhereReady($query)
    {
        return $query->where('status', 'Ready');
    }

    public function scopeWhereScheduled($query)
    {
        return $query->where('status', 'Scheduled');
    }

    public function scopeWhereFinished($query)
    {
        return $query->where('status', 'Finished');
    }
}
