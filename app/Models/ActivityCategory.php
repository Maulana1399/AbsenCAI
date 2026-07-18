<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityCategory extends Model
{
    protected $fillable = [
        'event_id',
        'activity_id',
        'category_definition_id',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function categoryDefinition()
    {
        return $this->belongsTo(CategoryDefinition::class);
    }
}
