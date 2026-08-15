<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventCommitteeAssignment extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'person_id',
        'participation_id',
        'event_role_id',
        'activity_group_id',
        'activity_id',
        'venue_id',
        'assigned_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function participation()
    {
        return $this->belongsTo(Participation::class);
    }

    public function eventRole()
    {
        return $this->belongsTo(EventRole::class);
    }

    public function activityGroup()
    {
        return $this->belongsTo(ActivityGroup::class);
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
