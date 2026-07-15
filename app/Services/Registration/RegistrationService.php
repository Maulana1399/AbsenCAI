<?php

namespace App\Services\Registration;

use App\Models\peserta;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class RegistrationService
{
    public function createParticipant(array $data): peserta
    {
        try {
            return peserta::create([
                'nama' => $data['nama'],
                'nip' => $data['nip'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'jenis_peserta' => $data['jenis_peserta'],
                'desa_id' => $data['desa_id'],
                'kelompok_id' => $data['kelompok_id'],
                'regu_id' => $data['regu_id'],
                'status_registrasi' => $data['status_registrasi'],
            ]);
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') {
                throw ValidationException::withMessages([
                    'nama' => 'Peserta dengan nama, desa, dan kelompok ini sudah terdaftar.',
                ]);
            }

            throw $exception;
        }
    }

    public function updateParticipantStatus(int $id, string $status): peserta
    {
        $peserta = peserta::findOrFail($id);

        $peserta->update([
            'status_registrasi' => $status,
        ]);

        return $peserta;
    }

    public function updateParticipant(int $id, array $data): peserta
    {
        $peserta = peserta::findOrFail($id);

        $peserta->update([
            'nama' => $data['nama'],
            'jenis_kelamin' => $data['jenis_kelamin'],
            'jenis_peserta' => $data['jenis_peserta'],
            'desa_id' => $data['desa_id'],
            'kelompok_id' => $data['kelompok_id'],
            'regu_id' => $data['regu_id'],
        ]);

        return $peserta;
    }
}
