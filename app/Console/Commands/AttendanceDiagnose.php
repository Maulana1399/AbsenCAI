<?php

namespace App\Console\Commands;

use App\Models\Absensi;
use Illuminate\Console\Command;

class AttendanceDiagnose extends Command
{
    protected $signature = 'attendance:diagnose
        {--event= : Diagnose specific Event ID}
        {--limit=20 : Max unmappable records to show}';

    protected $description = 'Identify why legacy attendance records are unmappable (read-only)';

    public function handle(): int
    {
        $eventId = $this->option('event') ? (int) $this->option('event') : null;
        $limit = (int) $this->option('limit');

        $query = Absensi::select('absensis.*')
            ->join('sesi_absensis', 'absensis.sesi_id', '=', 'sesi_absensis.id');

        if ($eventId !== null) {
            $query->where('sesi_absensis.event_id', $eventId);
        }

        $total = 0;
        $unmappable = [];

        $query->chunk(200, function ($absensis) use (&$total, &$unmappable, $limit) {
            foreach ($absensis as $absensi) {
                $total++;
                $reason = $this->diagnoseHadir($absensi);
                if ($reason !== null) {
                    $unmappable[] = $reason;
                    if (count($unmappable) >= $limit) {
                        return false;
                    }
                }
            }
        });

        $this->line('=== Attendance Diagnostic (READ-ONLY) ===');
        $this->line("Total legacy Absensi scanned: {$total}");
        $this->line('Unmappable found: '.count($unmappable));
        $this->newLine();

        if (empty($unmappable)) {
            $this->info('No unmappable records found.');

            return 0;
        }

        $headers = ['Absensi ID', 'NIP', 'Nama', 'Session ID', 'Event ID', 'Reason Code', 'Detail'];
        $rows = [];

        foreach ($unmappable as $r) {
            $rows[] = [
                $r['absensi_id'],
                $r['nip'],
                $r['nama'],
                $r['sesi_id'],
                $r['event_id'],
                $r['reason_code'],
                $r['detail'],
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();

        $this->warn('This command is READ-ONLY. No data was modified.');

        return 0;
    }

    private function diagnoseHadir(Absensi $absensi): ?array
    {
        $absensi->loadMissing('sesi');
        $eventId = $absensi->sesi?->event_id;

        if ($eventId === null) {
            return [
                'absensi_id' => $absensi->id,
                'nip' => $absensi->nip,
                'nama' => $absensi->nama,
                'sesi_id' => $absensi->sesi_id,
                'event_id' => 'NULL',
                'reason_code' => 'NO_EVENT',
                'detail' => 'SesiAbsensi has no event_id',
            ];
        }

        $legacyMapping = \App\Models\LegacyPesertaMapping::where('legacy_nip', (string) $absensi->nip)->first();
        $peserta = $legacyMapping?->peserta;

        if ($peserta === null) {
            return [
                'absensi_id' => $absensi->id,
                'nip' => $absensi->nip,
                'nama' => $absensi->nama,
                'sesi_id' => $absensi->sesi_id,
                'event_id' => $eventId,
                'reason_code' => 'NO_PESERTA',
                'detail' => "NIP {$absensi->nip} not found via LegacyPesertaMapping",
            ];
        }

        $mapping = \App\Models\LegacyParticipationMapping::where('peserta_id', $peserta->id)
            ->where('event_id', $eventId)
            ->first();

        if ($mapping === null) {
            $otherMappings = \App\Models\LegacyParticipationMapping::where('peserta_id', $peserta->id)->count();

            return [
                'absensi_id' => $absensi->id,
                'nip' => $absensi->nip,
                'nama' => $absensi->nama,
                'sesi_id' => $absensi->sesi_id,
                'event_id' => $eventId,
                'reason_code' => 'NO_MAPPING',
                'detail' => "Peserta ID {$peserta->id} exists but no mapping for event {$eventId} (has {$otherMappings} mappings for other events)",
            ];
        }

        $participation = $mapping->participation;

        if ($participation === null) {
            return [
                'absensi_id' => $absensi->id,
                'nip' => $absensi->nip,
                'nama' => $absensi->nama,
                'sesi_id' => $absensi->sesi_id,
                'event_id' => $eventId,
                'reason_code' => 'NO_PARTICIPATION',
                'detail' => "Mapping ID {$mapping->id} exists but participation is null",
            ];
        }

        if ((int) $participation->event_id !== $eventId) {
            return [
                'absensi_id' => $absensi->id,
                'nip' => $absensi->nip,
                'nama' => $absensi->nama,
                'sesi_id' => $absensi->sesi_id,
                'event_id' => $eventId,
                'reason_code' => 'EVENT_MISMATCH',
                'detail' => "Participation event_id={$participation->event_id} does not match session event_id={$eventId}",
            ];
        }

        return null;
    }
}
