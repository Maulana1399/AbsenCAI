<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class peserta extends Model
{
    public const STATUS_BELUM_REGISTRASI = 'Belum Registrasi';
    public const STATUS_SELF_REGISTER = 'Self Register';
    public const STATUS_REGISTRASI_ULANG = 'Registrasi Ulang';

    protected $fillable = ['nama', 'nip', 'jenis_kelamin', 'kelompok_id', 'desa_id', 'regu_id', 'status_registrasi'];

    public static function statusRegistrasiOptions(): array
    {
        return [
            self::STATUS_BELUM_REGISTRASI,
            self::STATUS_SELF_REGISTER,
            self::STATUS_REGISTRASI_ULANG,
        ];
    }

    public static function nextAutoNip(?string $jenisKelamin = null): int
    {
        if ($jenisKelamin === 'Laki - Laki') {

            $last = self::where(
                    'jenis_kelamin',
                    'Laki - Laki'
                )
                ->max('nip');


            return $last
                ? ((int)$last + 1)
                : 1001;
        }


        if ($jenisKelamin === 'Perempuan') {

            $last = self::where(
                    'jenis_kelamin',
                    'Perempuan'
                )
                ->max('nip');


            return $last
                ? ((int)$last + 1)
                : 2001;
        }


        return ((int)(self::max('nip') ?? 0)) + 1;
    }

    public static function autoPlacement(?string $jenisKelamin = null): array
    {
        $regu = self::leastFilledRegu($jenisKelamin);

        return [
            'nip' => (string) self::nextAutoNip($jenisKelamin),
            'regu_id' => $regu?->id,
            'regu_nama' => $regu?->regu ?? '-',
        ];
    }

    public static function leastFilledRegu(?string $jenisKelamin = null): ?regu
    {
        $query = regu::withCount('peserta');

        if ($jenisKelamin) {
            $filtered = (clone $query)->where('jenis_kelamin', $jenisKelamin)->orderBy('peserta_count')->orderBy('id')->first();

            if ($filtered) {
                return $filtered;
            }
        }

        return $query
            ->orderBy('peserta_count')
            ->orderBy('id')
            ->first();
    }

    public static function leastFilledReguId(?string $jenisKelamin = null): ?int
    {
        return self::leastFilledRegu($jenisKelamin)?->id;
    }

    public static function leastFilledReguName(?string $jenisKelamin = null): string
    {
        return self::leastFilledRegu($jenisKelamin)?->regu ?? '-';
    }

    public function getStatusRegistrasiLabelAttribute(): string
    {
        return $this->status_registrasi ?: self::STATUS_BELUM_REGISTRASI;
    }
    
    public function kelompok() {
        return $this->belongsTo(kelompok::class);
    }

    public function desa() {
        return $this->belongsTo(desa::class);
    }

    public function regu() {
        return $this->belongsTo(regu::class);
    }
}

    
