<?php

namespace App\Livewire\SuratIzin;

use App\Models\Person;
use App\Models\peserta;
use App\Services\Attendance\LegacyParticipationResolver;
use App\Services\Attendance\SuratIzinService;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Create extends Component
{
    public $searchPeserta = '';

    public $selectedPesertaId = null;

    public $selectedPesertaNama = '';

    public $alasan = '';

    public $jenisIzin = 'pulang';

    public $tanggal_mulai = '';

    public $tanggal_selesai = '';

    public bool $processing = false;

    public function render()
    {
        $results = [];
        if (strlen($this->searchPeserta) >= 2) {
            $search = '%'.$this->searchPeserta.'%';

            $event = app(ActiveEventContext::class)->current();
            $resolver = app(LegacyParticipationResolver::class);

            $persons = Person::where('nama', 'like', $search)
                ->limit(10)
                ->get()
                ->map(function ($person) use ($event, $resolver) {
                    $participation = $event ? $resolver->resolveByPersonAndEvent($person->id, $event->id) : null;

                    return (object) [
                        'id' => $person->id,
                        'nama' => $person->nama,
                        'source' => 'canonical',
                        'peserta_id' => $participation ? $resolver->resolvePesertaByParticipation($participation->id, $event?->id)?->id : null,
                    ];
                });

            $personNames = $persons->pluck('nama');

            $legacyPesertas = peserta::where(function ($q) use ($search) {
                $q->where('nama', 'like', $search)
                    ->orWhere('attendance_code', 'like', $search);
            })
                ->whereNotIn('nama', $personNames)
                ->limit(10)
                ->get()
                ->map(fn ($p) => (object) [
                'id' => $p->id,
                'nama' => $p->nama,
                'source' => 'legacy',
                'peserta_id' => $p->id,
            ]);

            $results = $persons->merge($legacyPesertas)->take(10);
        }

        return view('livewire.surat-izin.create', ['results' => $results]);
    }

    public function selectPeserta(int $id, ?string $source = null)
    {
        $event = app(\App\Support\ActiveEventContext::class)->current();

        $resolver = app(LegacyParticipationResolver::class);

        if ($source === 'canonical') {
            $person = Person::find($id);
            if ($person && $event) {
                $participation = $resolver->resolveByPersonAndEvent($person->id, $event->id);

                if ($participation) {
                    $selectedPeserta = $resolver->resolvePesertaByParticipation($participation->id, $event->id);
                    if ($selectedPeserta) {
                        $this->selectedPesertaId = $selectedPeserta->id;
                        $this->selectedPesertaNama = $person->nama;
                        $this->searchPeserta = '';

                        return;
                    }
                }
            }
        }

        if ($event) {
            $participation = $resolver->resolveByPesertaAndEvent($id, $event->id);
            if ($participation) {
                $selectedPeserta = $resolver->resolvePesertaByParticipation($participation->id, $event->id);
                if ($selectedPeserta) {
                    $this->selectedPesertaId = $selectedPeserta->id;
                    $this->selectedPesertaNama = $participation->person->nama;
                    $this->searchPeserta = '';

                    return;
                }
            }
        }

        $p = peserta::find($id);
        if ($p) {
            $this->selectedPesertaId = $p->id;
            $this->selectedPesertaNama = $p->nama;
            $this->searchPeserta = '';
        }
    }

    public function saveDraft()
    {
        Gate::authorize('manage-secretariat');

        if ($this->processing) {
            return;
        }
        $this->processing = true;
        try {
            $this->validate();
            app(SuratIzinService::class)->create(
                [
                    'peserta_id' => $this->selectedPesertaId,
                    'alasan' => $this->alasan,
                    'jenis_izin' => $this->jenisIzin,
                    'tanggal_mulai' => $this->tanggal_mulai,
                    'tanggal_selesai' => $this->tanggal_selesai,
                ],
                auth()->id()
            );
            $this->resetForm();
            session()->flash('success', 'Surat izin berhasil dibuat sebagai draft.');
            $this->dispatch('suratIzinSaved');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            if (isset($errors['peserta_id'])) {
                $errors['selectedPesertaId'] = $errors['peserta_id'];
                unset($errors['peserta_id']);
            }
            $this->setErrorBag($errors);
        } finally {
            $this->processing = false;
        }
    }

    public function saveAndSubmit()
    {
        Gate::authorize('manage-secretariat');

        if ($this->processing) {
            return;
        }
        $this->processing = true;
        try {
            $this->validate();
            $service = app(SuratIzinService::class);
            $surat = $service->create(
                [
                    'peserta_id' => $this->selectedPesertaId,
                    'alasan' => $this->alasan,
                    'jenis_izin' => $this->jenisIzin,
                    'tanggal_mulai' => $this->tanggal_mulai,
                    'tanggal_selesai' => $this->tanggal_selesai,
                ],
                auth()->id()
            );
            $service->submit($surat);
            $this->resetForm();
            session()->flash('success', 'Surat izin berhasil dibuat dan disubmit.');
            $this->dispatch('suratIzinSaved');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            if (isset($errors['peserta_id'])) {
                $errors['selectedPesertaId'] = $errors['peserta_id'];
                unset($errors['peserta_id']);
            }
            $this->setErrorBag($errors);
        } finally {
            $this->processing = false;
        }
    }

    public function resetForm()
    {
        $this->reset(['selectedPesertaId', 'selectedPesertaNama', 'alasan', 'jenisIzin', 'tanggal_mulai', 'tanggal_selesai', 'searchPeserta']);
        $this->jenisIzin = 'pulang';
    }

    protected function rules()
    {
        return [
            'selectedPesertaId' => 'required|exists:pesertas,id',
            'alasan' => 'required|min:5',
            'jenisIzin' => 'required|in:pulang,keluar',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ];
    }

    protected $messages = [
        'selectedPesertaId.required' => 'Pilih peserta terlebih dahulu.',
        'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
    ];
}
