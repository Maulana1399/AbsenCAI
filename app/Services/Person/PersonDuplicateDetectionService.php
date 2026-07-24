<?php

namespace App\Services\Person;

use App\Models\Person;

class PersonDuplicateDetectionService
{
    const STRONG_SIMILARITY = 100;
    const POSSIBLE_SIMILARITY = 80;

    public function normalizeName(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtolower($name)));
    }

    public function detect(array $input): array
    {
        $normalizedName = $this->normalizeName($input['nama'] ?? '');
        $tanggalLahir = $input['tanggal_lahir'] ?? null;
        $jenisKelamin = $input['jenis_kelamin'] ?? null;
        $desaId = $input['desa_id'] ?? null;

        $all = Person::all();
        $strong = [];
        $possible = [];

        foreach ($all as $person) {
            $personNormalized = $this->normalizeName($person->nama);

            if ($personNormalized === $normalizedName) {
                if ($tanggalLahir && $person->tanggal_lahir) {
                    $tlInput = $tanggalLahir instanceof \Carbon\Carbon
                        ? $tanggalLahir->format('Y-m-d')
                        : $tanggalLahir;
                    $tlPerson = $person->tanggal_lahir instanceof \Carbon\Carbon
                        ? $person->tanggal_lahir->format('Y-m-d')
                        : $person->tanggal_lahir;
                    if ($tlInput === $tlPerson) {
                        $strong[] = $person;
                    } else {
                        $possible[] = $person;
                    }
                } else {
                    $possible[] = $person;
                }
                continue;
            }

            similar_text($personNormalized, $normalizedName, $percent);

            if ($percent >= self::POSSIBLE_SIMILARITY) {
                if ($tanggalLahir && $person->tanggal_lahir) {
                    $tlInput = $tanggalLahir instanceof \Carbon\Carbon
                        ? $tanggalLahir->format('Y-m-d')
                        : $tanggalLahir;
                    $tlPerson = $person->tanggal_lahir instanceof \Carbon\Carbon
                        ? $person->tanggal_lahir->format('Y-m-d')
                        : $person->tanggal_lahir;
                    if ($tlInput === $tlPerson) {
                        $possible[] = $person;
                        continue;
                    }
                }
                $possible[] = $person;
            }
        }

        return [
            'strong' => $strong,
            'possible' => $possible,
            'normalized_name' => $normalizedName,
        ];
    }
}
