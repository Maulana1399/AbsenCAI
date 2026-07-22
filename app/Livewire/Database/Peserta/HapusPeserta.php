<?php

namespace App\Livewire\Database\Peserta;

use App\Models\EventAttendance;
use App\Models\IzinAbsensi;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\SuratIzin;
use App\Models\peserta;
use App\Services\Attendance\LegacyParticipationResolver;
use App\Support\ActiveEventContext;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class HapusPeserta extends Component
{
    public $peserta_id;
    public $peserta;
    public ?string $blockReason = null;
    public bool $canDelete = false;
    public ?int $participation_id = null;

    #[On("HapusPeserta")]
    public function hapusPeserta($id)
    {
        $event = app(ActiveEventContext::class)->current();
        if ($event === null) {
            $this->blockReason = 'Tidak ada event aktif.';
            $this->canDelete = false;
            Flux::modal("hapus-peserta")->show();
            return;
        }

        $resolver = app(LegacyParticipationResolver::class);
        $participation = Participation::with(['person', 'event'])->find($id);

        if ($participation === null || (int) $participation->event_id !== (int) $event->id) {
            $participation = $resolver->resolveByPesertaAndEvent((int) $id, $event->id);
        }

        if ($participation === null) {
            $this->blockReason = 'Peserta tidak memiliki keanggotaan pada event aktif.';
            $this->canDelete = false;
            Flux::modal("hapus-peserta")->show();
            return;
        }

        $this->participation_id = $participation->id;
        $legacyPeserta = $resolver->resolvePesertaByParticipation($participation->id, $event->id);

        $this->peserta_id = $legacyPeserta?->id;
        $this->peserta = $participation->person?->nama ?? $legacyPeserta?->nama;

        $reasons = [];

        if (EventAttendance::where('participation_id', $participation->id)->exists()) {
            $reasons[] = 'Memiliki data kehadiran event';
        }

        if (SuratIzin::where('participation_id', $participation->id)->exists()) {
            $reasons[] = 'Memiliki surat izin event';
        }

        if (IzinAbsensi::where('peserta_id', $legacyPeserta?->id)->whereHas('sesi', fn ($q) => $q->where('event_id', $event->id))->exists()) {
            $reasons[] = 'Memiliki data izin absensi pada event ini';
        }

        $legacyPointer = LegacyPesertaMapping::where('peserta_id', $legacyPeserta?->id)->first();
        $survivingParticipationExists = LegacyParticipationMapping::query()
            ->where('peserta_id', $legacyPeserta?->id)
            ->where('participation_id', '!=', $participation->id)
            ->exists();

        if ($legacyPointer !== null && (int) $legacyPointer->participation_id === (int) $participation->id && ! $survivingParticipationExists) {
            $reasons[] = 'Membership event terakhir tidak dapat dihapus karena masih diperlukan data kompatibilitas legacy.';
        }

        if (! empty($reasons)) {
            $this->blockReason = 'Peserta tidak dapat dihapus dari event ini karena:<br>' . implode('<br>', $reasons);
            $this->canDelete = false;
        } else {
            $this->blockReason = null;
            $this->canDelete = true;
        }

        Flux::modal("hapus-peserta")->show();
    }

    public function destroy()
    {
        Gate::authorize('manage-participants');

        if (! $this->canDelete || ! $this->participation_id) {
            return;
        }

        $event = app(ActiveEventContext::class)->current();
        if ($event === null) {
            return;
        }

        DB::transaction(function () use ($event) {
            $participation = Participation::with(['person', 'legacyPesertaMapping'])->findOrFail($this->participation_id);
            if ((int) $participation->event_id !== (int) $event->id) {
                return;
            }

            $mapping = LegacyParticipationMapping::where('participation_id', $participation->id)
                ->where('event_id', $event->id)
                ->first();

            if ($mapping === null) {
                return;
            }

            $legacyPeserta = $mapping->peserta;
            $survivingParticipation = LegacyParticipationMapping::query()
                ->where('peserta_id', $legacyPeserta?->id)
                ->where('participation_id', '!=', $participation->id)
                ->orderBy('participation_id')
                ->first()?->participation;

            if ($survivingParticipation === null) {
                $this->blockReason = 'Membership event terakhir tidak dapat dihapus karena masih diperlukan data kompatibilitas legacy.';
                $this->canDelete = false;
                return;
            }

            $legacyPointer = LegacyPesertaMapping::where('peserta_id', $legacyPeserta->id)
                ->first();

            if ($legacyPointer !== null && (int) $legacyPointer->participation_id === (int) $participation->id) {
                $legacyPointer->update([
                    'participation_id' => $survivingParticipation->id,
                    'person_id' => $survivingParticipation->person_id,
                ]);
            }

            $mapping->delete();
            $participation->delete();
        });

        $this->dispatch('refreshPeserta');
        Flux::modal("hapus-peserta")->close();
    }

    public function render()
    {
        return view('livewire..database.peserta.hapus-peserta');
    }
}
