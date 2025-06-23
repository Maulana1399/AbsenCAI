<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Peserta;

class Absensi extends Model
{
    protected $fillable = ['nip', 'nama', 'jam_scan'];

    public function peserta()
    {
        return $this->belongsTo(Peserta::class, 'nip', 'nip');
    }
}
