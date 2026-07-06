<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class regu extends Model
{
    protected $fillable = [
    'regu',
    'jenis_kelamin',
    ];
    public function peserta() {
        return $this->hasMany(peserta::class);
    }
}
