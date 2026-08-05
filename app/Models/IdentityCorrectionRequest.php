<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdentityCorrectionRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'person_id',
        'event_id',
        'desa_id',
        'requested_name',
        'requested_birth_date',
        'requested_desa_id',
        'reason',
        'operator_notes',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'requested_birth_date' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'metadata' => 'json',
        ];
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function desa()
    {
        return $this->belongsTo(desa::class);
    }

    public function requestedDesa()
    {
        return $this->belongsTo(desa::class, 'requested_desa_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
