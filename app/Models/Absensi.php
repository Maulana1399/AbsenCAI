<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\peserta;

class Absensi extends Model
{
    protected $fillable = ['nip', 'nama', 'jam_scan', 'sesi_id'];

    public function peserta()
    {
        return $this->belongsTo(peserta::class, 'nip', 'nip');
    }

    public function sesi()
    {
        return $this->belongsTo(SesiAbsensi::class, 'sesi_id');
    }
}