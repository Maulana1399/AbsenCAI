<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitionTeam extends Model
{
    protected $fillable = [
        'event_id',
        'competition_class_id',
        'name',
        'kelompok_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function competitionClass()
    {
        return $this->belongsTo(CompetitionClass::class);
    }

    public function kelompok()
    {
        return $this->belongsTo(kelompok::class);
    }

    public function members()
    {
        return $this->hasMany(CompetitionTeamMember::class)
            ->orderBy('is_substitute')
            ->orderBy('sort_order');
    }

    public function players()
    {
        return $this->hasMany(CompetitionTeamMember::class)
            ->where('is_substitute', false)
            ->orderBy('sort_order');
    }

    public function substitutes()
    {
        return $this->hasMany(CompetitionTeamMember::class)
            ->where('is_substitute', true)
            ->orderBy('sort_order');
    }

    public function scopeWhereEvent($query, int $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeWhereClass($query, int $classId)
    {
        return $query->where('competition_class_id', $classId);
    }

    public function outcome()
    {
        return $this->hasOne(CompetitionTeamOutcome::class);
    }

    public function scheduleEntries()
    {
        return $this->hasMany(CompetitionScheduleEntry::class, 'competition_team_id');
    }

    public function heatResults()
    {
        return $this->hasMany(CompetitionHeatResult::class, 'competition_team_id');
    }
}
