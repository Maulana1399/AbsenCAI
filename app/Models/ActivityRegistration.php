<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityRegistration extends Model
{
    protected $fillable = [
        'event_id',
        'participation_id',
        'activity_id',
        'category_definition_id',
        'status',
        'registered_at',
        'source',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function participation()
    {
        return $this->belongsTo(Participation::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function categoryDefinition()
    {
        return $this->belongsTo(CategoryDefinition::class, 'category_definition_id');
    }
}
