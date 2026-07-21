<?php

namespace App\Livewire\Rekap\Peserta;

use App\Exports\PesertaExport;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\Participation;
use App\Models\regu;
use App\Services\Audit\ActivityLogService;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class RekapPeserta extends Component
{
    public $regu_id = '';
    public $kelompok_id = '';
    public $desa_id = '';
    public $jenis_kelamin = '';
    public $jenis_peserta = '';

    public $daftarRegu = [];
    public $daftarKelompok = [];
    public $daftarDesa = [];

    public function mount()
    {
        $this->daftarRegu = regu::all();
        $this->daftarKelompok = kelompok::with('desa')->get();
        $this->daftarDesa = desa::all();
    }

    public function render()
    {
        $event = app(ActiveEventContext::class)->current();
        $query = Participation::with(['person.desa', 'person.legacyPesertaMapping.peserta', 'event']);

        if ($event !== null) {
            $query->where('event_id', $event->id);
        } else {
            $query->whereRaw('0 = 1');
        }

        if ($this->regu_id) {
            $query->whereHas('person.legacyPesertaMapping.peserta', fn ($builder) => $builder->where('regu_id', $this->regu_id));
        }

        if ($this->kelompok_id) {
            $query->whereHas('person.legacyPesertaMapping.peserta', fn ($builder) => $builder->where('kelompok_id', $this->kelompok_id));
        }

        if ($this->desa_id) {
            $query->whereHas('person', fn ($builder) => $builder->where('desa_id', $this->desa_id));
        }

        if ($this->jenis_kelamin) {
            $gender = $this->jenis_kelamin === 'Laki - Laki' ? 'L' : 'P';
            $query->whereHas('person', fn ($builder) => $builder->where('jenis_kelamin', $gender));
        }

        if ($this->jenis_peserta) {
            $query->where('jenis_peserta', $this->jenis_peserta);
        }

        $daftar = $query->orderBy('id')->get()->map(function (Participation $participation) {
            $person = $participation->person;
            $peserta = $person?->legacyPesertaMapping?->peserta;

            return (object) [
                'id' => $participation->id,
                'person' => $person,
                'nama' => $person?->nama,
                'nip' => $person?->nip,
                'jenis_kelamin' => $person?->jenis_kelamin,
                'jenis_peserta' => $participation->jenis_peserta,
                'participant_number' => $participation->participant_number,
                'attendance_code' => $participation->attendance_code,
                'desa' => $person?->desa,
                'kelompok' => $peserta?->kelompok,
                'regu' => $peserta?->regu,
                'status_registrasi' => $peserta?->status_registrasi,
                'status_registrasi_label' => $peserta?->status_registrasi_label ?? 'Belum Registrasi',
            ];
        })->values();

        $total = $daftar->count();
        $totalLaki = $daftar->where('jenis_kelamin','L')->count();
        $totalPerempuan = $daftar->where('jenis_kelamin','P')->count();
        $sudahRegUlang = $daftar->where('status_registrasi','Registrasi Ulang')->count();
        $belumRegUlang = $daftar->where('status_registrasi','Belum Registrasi')->count();

        return view('livewire.rekap.peserta.rekap-peserta', [
            'daftarPeserta' => $daftar,
            'total' => $total,
            'totalLaki' => $totalLaki,
            'totalPerempuan' => $totalPerempuan,
            'sudahRegUlang' => $sudahRegUlang,
            'belumRegUlang' => $belumRegUlang,
        ]);
    }

    public function exportExcel()
    {
        Gate::authorize('view-reports');

        $eventId = app(ActiveEventContext::class)->current()?->id;

        if ($eventId === null) {
            session()->flash('error', 'Pilih event terlebih dahulu.');

            return;
        }

        $fileName = 'rekap-peserta-'.now()->format('YmdHis').'.xlsx';

        $filters = [];
        $this->regu_id && $filters['regu_id'] = $this->regu_id;
        $this->kelompok_id && $filters['kelompok_id'] = $this->kelompok_id;
        $this->desa_id && $filters['desa_id'] = $this->desa_id;
        $this->jenis_kelamin && $filters['jenis_kelamin'] = $this->jenis_kelamin;
        $this->jenis_peserta && $filters['jenis_peserta'] = $this->jenis_peserta;

        $pesertaExport = new PesertaExport(
            $eventId,
            $this->regu_id,
            $this->kelompok_id,
            $this->desa_id,
            $this->jenis_kelamin,
            $this->jenis_peserta
        );

        app(ActivityLogService::class)->log(
            action: 'exported',
            module: 'export',
            description: 'Mengekspor data peserta',
            properties: [
                'export_type' => 'peserta',
                'format'      => 'xlsx',
                'filename'    => $fileName,
                'filters'     => $filters,
            ],
        );

        return Excel::download($pesertaExport, $fileName);
    }
}
