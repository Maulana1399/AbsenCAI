<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Carbon\Carbon;
use App\Models\Absensi;
use App\Models\peserta;
use App\Models\SesiAbsensi;

class Scan extends Component
{
    public $nama;
    public $nip;
    public $jam_scan;
    public $message;

    public $sesi_id = '';
    public $daftarSesi;

    public $restartScanner = false;


    public function mount()
    {
        $this->daftarSesi = SesiAbsensi::orderBy('tanggal', 'asc')->get();

        $sesiAktif = SesiAbsensi::where('aktif', true)->first();

        if ($sesiAktif) {
            $this->sesi_id = $sesiAktif->id;
        }
    }


    public function scanPeserta($data)
    {
        $nip = trim($data);


        // cek peserta
        $peserta = peserta::where('nip', $nip)->first();

        if (!$peserta) {

            $this->message = "Data peserta tidak ditemukan!";

            $this->nama = null;
            $this->nip = null;
            $this->jam_scan = null;

            return;
        }


        // cek sesi pilihan
        $sesi = SesiAbsensi::find($this->sesi_id);

        if (!$sesi) {

            $this->message = "Pilih sesi absensi terlebih dahulu";

            return;
        }


        $this->nip = $peserta->nip;
        $this->nama = $peserta->nama;
        $this->jam_scan = Carbon::now()->format('Y-m-d H:i:s');


        // cek sudah absen sesi ini
        $last = Absensi::where('nip', $nip)
            ->where('sesi_id', $sesi->id)
            ->first();


        if ($last) {

            $this->message = "Peserta sudah absen pada sesi ini";

            return;
        }


        Absensi::create([
            'nip' => $peserta->nip,
            'nama' => $peserta->nama,
            'jam_scan' => $this->jam_scan,
            'sesi_id' => $sesi->id,
        ]);


        $this->message = "Absensi berhasil!";
    }


    public function restartScan()
    {
        $this->nama = null;
        $this->nip = null;
        $this->jam_scan = null;
        $this->message = null;

        $this->dispatch('restartScanner');
    }


    public function render()
    {
        return view('livewire.dashboard.scan');
    }
}