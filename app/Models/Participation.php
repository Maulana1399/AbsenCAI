<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participation extends Model
{
    protected $fillable = [
        'person_id',
        'event_id',
        'participant_number',
        'attendance_code',
        'jenis_peserta',
        'regu_id',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function regu()
    {
        return $this->belongsTo(regu::class);
    }

    public function legacyParticipationMapping()
    {
        return $this->hasOne(LegacyParticipationMapping::class, 'participation_id');
    }

    public function activityRegistrations()
    {
        return $this->hasMany(ActivityRegistration::class);
    }

    public function committeeAssignments()
    {
        return $this->hasMany(EventCommitteeAssignment::class);
    }

    public function eventAttendances()
    {
        return $this->hasMany(EventAttendance::class);
    }
}
