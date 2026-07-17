<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SesiAbsensi extends Model
{
    protected $fillable = ['event_id', 'nama_sesi', 'tanggal', 'aktif'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function absensis()
    {
        return $this->hasMany(Absensi::class, 'sesi_id');
    }
}
