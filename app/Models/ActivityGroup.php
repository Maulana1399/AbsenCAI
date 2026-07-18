<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityGroup extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'slug',
        'sort_order',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }
}
