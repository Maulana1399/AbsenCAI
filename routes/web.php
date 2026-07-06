<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Registrasi\SelfRegister;
use App\Http\Controllers\ImportDataController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('registrasi', 'registrasi.peserta')
    ->middleware(['auth', 'verified'])
    ->name('registrasi.peserta');

Route::get('registrasi/self', SelfRegister::class)
    ->middleware(['auth', 'verified'])
    ->name('registrasi.self');

Route::view('registrasi/ulang', 'registrasi.ulang')
    ->middleware(['auth', 'verified'])
    ->name('registrasi.ulang');

Route::view('database', 'database.database')
    ->middleware(['auth', 'verified'])
    ->name('database');

Route::view('desa', 'database.desa')
    ->middleware(['auth', 'verified'])
    ->name('desa');

Route::view('kelompok', 'database.kelompok')
    ->middleware(['auth', 'verified'])
    ->name('kelompok');

Route::view('regu', 'database.regu')
    ->middleware(['auth', 'verified'])
    ->name('regu');

Route::view('sesi-absensi', 'database.sesi')
    ->middleware(['auth', 'verified'])
    ->name('sesi.absensi');

Route::view('rekap-peserta', 'rekap.peserta')
    ->middleware(['auth', 'verified'])
    ->name('rekap.peserta');

Route::view('rekap-absensi', 'rekap.absensi')
    ->middleware(['auth', 'verified'])
    ->name('rekap.absensi');

Route::view('absensi', 'dashboard.absensi')
    ->middleware(['auth', 'verified'])
    ->name('absensi');


Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('import/desa', [ImportDataController::class, 'desa'])->name('import.desa');
    Route::post('import/kelompok', [ImportDataController::class, 'kelompok'])->name('import.kelompok');
    Route::post('import/regu', [ImportDataController::class, 'regu'])->name('import.regu');
    Route::post('import/peserta', [ImportDataController::class, 'peserta'])->name('import.peserta');
});

require __DIR__.'/auth.php';
