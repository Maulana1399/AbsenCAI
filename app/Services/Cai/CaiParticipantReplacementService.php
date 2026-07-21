<?php

namespace App\Services\Cai;

use App\Models\Absensi;
use App\Models\ActivityRegistration;
use App\Models\EventAttendance;
use App\Models\EventCommitteeAssignment;
use App\Models\IzinAbsensi;
use App\Models\LegacyPesertaMapping;
use App\Models\peserta;
use App\Models\SuratIzin;
use App\Models\CaiParticipantReplacement;
use App\Models\Participation;
use App\Models\Person;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CaiParticipantReplacementService
{
    public function assertReplaceable(peserta $peserta): LegacyPesertaMapping
    {
        $mapping = LegacyPesertaMapping::query()
            ->where('peserta_id', $peserta->id)
            ->first();

        if (! $mapping) {
            throw new RuntimeException(
                'Peserta belum memiliki mapping identitas V2 dan tidak dapat diganti.'
            );
        }

        if (Absensi::query()->where('nip', $peserta->nip)->exists()) {
            throw new RuntimeException(
                'Peserta sudah memiliki riwayat absensi dan tidak dapat diganti.'
            );
        }

        if (IzinAbsensi::query()->where('peserta_id', $peserta->id)->exists()) {
            throw new RuntimeException(
                'Peserta sudah memiliki riwayat izin absensi dan tidak dapat diganti.'
            );
        }

        if (SuratIzin::query()->where('peserta_id', $peserta->id)->exists()) {
            throw new RuntimeException(
                'Peserta sudah memiliki surat izin dan tidak dapat diganti.'
            );
        }

        if (
            ActivityRegistration::query()
                ->where('participation_id', $mapping->participation_id)
                ->exists()
        ) {
            throw new RuntimeException(
                'Peserta sudah terdaftar pada aktivitas dan tidak dapat diganti.'
            );
        }

        if (
            EventAttendance::query()
                ->where('participation_id', $mapping->participation_id)
                ->exists()
        ) {
            throw new RuntimeException(
                'Peserta sudah memiliki riwayat kehadiran event dan tidak dapat diganti.'
            );
        }

        if (
            EventCommitteeAssignment::query()
                ->where(function ($query) use ($mapping) {
                    $query
                        ->where('participation_id', $mapping->participation_id)
                        ->orWhere('person_id', $mapping->person_id);
                })
                ->exists()
        ) {
            throw new RuntimeException(
                'Peserta sudah memiliki penugasan kepanitiaan dan tidak dapat diganti.'
            );
        }

        return $mapping;
    }
    public function replace(
    peserta $peserta,
    array $replacementData,
    ?string $reason = null,
    ): array {

        $nama = trim((string) ($replacementData['nama'] ?? ''));

        if ($nama === '') {
            throw new RuntimeException('Nama peserta pengganti wajib diisi.');
        }

        return DB::transaction(function () use ($peserta, $replacementData, $reason, $nama) {
            // Lock row peserta agar dua proses replacement tidak berjalan bersamaan.
            $peserta = peserta::query()
                ->lockForUpdate()
                ->findOrFail($peserta->id);

            // Validasi ulang DI DALAM transaction.
            $mapping = $this->assertReplaceable($peserta);
            $event = \App\Models\Event::findOrFail($mapping->event_id);

            if (! $event->isCai()) {
                throw new RuntimeException(
                    'Penggantian peserta hanya dapat dilakukan pada event CAI.'
                );
            }

            $oldPerson = Person::query()
                ->lockForUpdate()
                ->findOrFail($mapping->person_id);

            $oldParticipation = Participation::query()
                ->lockForUpdate()
                ->findOrFail($mapping->participation_id);

            /*
            * Simpan identitas slot CAI.
            *
            * Identifier ini milik SLOT peserta pada event,
            * bukan identitas personal orang lama.
            */
            $legacyNip = $peserta->nip;
            $participantNumber = $peserta->participant_number;
            $attendanceCode = $peserta->attendance_code;

            /*
            * Lepaskan identifier unik dari Participation lama terlebih dahulu
            * agar dapat digunakan oleh Participation pengganti.
            *
            * Participation lama TIDAK dihapus untuk menjaga histori.
            */
            $oldParticipation->update([
                'participant_number' => null,
                'attendance_code' => null,
            ]);

            // Lepaskan NIP dari identitas lama agar dapat dipindahkan
            // ke Person pengganti. Person lama tetap disimpan sebagai histori.

            $oldPerson->update([
                'nip' => null,
            ]);

            /*
            * Person baru mewakili orang pengganti.
            *
            * Desa dan kelompok tetap mengikuti slot CAI lama.
            * NIP juga tetap mengikuti kontrak legacy.
            */
            $newPerson = Person::create([
                'nama' => $nama,
                'jenis_kelamin' => $this->normalizeGender(
                    $replacementData['jenis_kelamin'] ?? $peserta->jenis_kelamin
                ),
                'tanggal_lahir' => $replacementData['tanggal_lahir'] ?? null,
                'desa_id' => $peserta->desa_id,
                'kelompok_id' => $peserta->kelompok_id,
                'nip' => $legacyNip,
            ]);

            /*
            * Participation baru mengambil slot event milik peserta lama.
            */
            $newParticipation = Participation::create([
                'person_id' => $newPerson->id,
                'event_id' => $mapping->event_id,
                'participant_number' => $participantNumber,
                'attendance_code' => $attendanceCode,
                'jenis_peserta' => $peserta->jenis_peserta,
            ]);

            /*
            * Legacy peserta tetap memakai ID row yang sama karena banyak
            * modul CAI lama masih mereferensikan peserta.id / nip.
            *
            * Yang berubah hanya identitas orangnya.
            */
            $peserta->update([
                'nama' => trim($replacementData['nama']),
                'jenis_kelamin' => $replacementData['jenis_kelamin']
                    ?? $peserta->jenis_kelamin,
            ]);

            /*
            * Mapping legacy sekarang menunjuk Person + Participation baru.
            */
            $mapping->update([
                'person_id' => $newPerson->id,
                'participation_id' => $newParticipation->id,
            ]);

            /*
            * Catat audit trail replacement.
            */
            $replacement = CaiParticipantReplacement::create([
                'event_id' => $mapping->event_id,
                'peserta_id' => $peserta->id,

                'old_person_id' => $oldPerson->id,
                'old_participation_id' => $oldParticipation->id,

                'new_person_id' => $newPerson->id,
                'new_participation_id' => $newParticipation->id,

                'legacy_nip' => $legacyNip,
                'participant_number' => $participantNumber,
                'attendance_code' => $attendanceCode,

                'desa_id' => $peserta->desa_id,
                'kelompok_id' => $peserta->kelompok_id,
                'regu_id' => $peserta->regu_id,

                'reason' => $reason,
                'replaced_by' => auth()->id(),
                'replaced_at' => now(),
            ]); 

            return [
                'peserta' => $peserta->fresh(),
                'person' => $newPerson->fresh(),
                'participation' => $newParticipation->fresh(),
                'mapping' => $mapping->fresh(),
                'replacement' => $replacement,
            ];
        });
    }

    private function normalizeGender(?string $gender): ?string
    {
        return match (trim((string) $gender)) {
            'L', 'Laki - Laki', 'Laki-laki', 'Laki laki' => 'L',
            'P', 'Perempuan' => 'P',
            default => null,
        };
    }
}
