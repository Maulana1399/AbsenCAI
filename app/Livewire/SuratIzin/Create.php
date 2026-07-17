<?php

namespace App\Livewire\SuratIzin;

use App\Models\peserta;
use App\Services\Attendance\SuratIzinService;
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
            $results = peserta::where(function ($q) {
                $q->where('nama', 'like', '%' . $this->searchPeserta . '%')
                  ->orWhere('nip', 'like', '%' . $this->searchPeserta . '%')
                  ->orWhere('attendance_code', 'like', '%' . $this->searchPeserta . '%');
            })->limit(10)->get();
        }

        return view('livewire.surat-izin.create', ['results' => $results]);
    }

    public function selectPeserta(int $id)
    {
        $p = peserta::find($id);
        if ($p) {
            $this->selectedPesertaId = $p->id;
            $this->selectedPesertaNama = $p->nama . ' (' . $p->nip . ')';
            $this->searchPeserta = '';
        }
    }

    public function saveDraft()
    {
        if ($this->processing) return;
        $this->processing = true;
        try {
            $this->validate();
            app(SuratIzinService::class)->create(
                [
                    'peserta_id'      => $this->selectedPesertaId,
                    'alasan'          => $this->alasan,
                    'jenis_izin'      => $this->jenisIzin,
                    'tanggal_mulai'   => $this->tanggal_mulai,
                    'tanggal_selesai' => $this->tanggal_selesai,
                ],
                auth()->id()
            );
            $this->resetForm();
            session()->flash('success', 'Surat izin berhasil dibuat sebagai draft.');
            $this->dispatch('suratIzinSaved');
        } finally {
            $this->processing = false;
        }
    }

    public function saveAndSubmit()
    {
        if ($this->processing) return;
        $this->processing = true;
        try {
            $this->validate();
            $service = app(SuratIzinService::class);
            $surat = $service->create(
                [
                    'peserta_id'      => $this->selectedPesertaId,
                    'alasan'          => $this->alasan,
                    'jenis_izin'      => $this->jenisIzin,
                    'tanggal_mulai'   => $this->tanggal_mulai,
                    'tanggal_selesai' => $this->tanggal_selesai,
                ],
                auth()->id()
            );
            $service->submit($surat);
            $this->resetForm();
            session()->flash('success', 'Surat izin berhasil dibuat dan disubmit.');
            $this->dispatch('suratIzinSaved');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->setErrorBag($e->errors());
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
            'alasan'            => 'required|min:5',
            'jenisIzin'         => 'required|in:pulang,keluar',
            'tanggal_mulai'     => 'required|date',
            'tanggal_selesai'   => 'required|date|after_or_equal:tanggal_mulai',
        ];
    }

    public function messages()
    {
        return [
            'selectedPesertaId.required' => 'Pilih peserta terlebih dahulu.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
        ];
    }
}
