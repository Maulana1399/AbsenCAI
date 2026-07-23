<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyPesertaMapping extends Model
{
    protected $fillable = [
        'peserta_id',
        'person_id',
        'legacy_nip',
        'legacy_participant_number',
        'legacy_attendance_code',
        'migrated_at',
    ];

    protected function casts(): array
    {
        return [
            'migrated_at' => 'datetime',
        ];
    }

    public function peserta()
    {
        return $this->belongsTo(peserta::class);
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
