<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

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

Route::view('absensi', 'dashboard.absensi')
    ->middleware(['auth', 'verified'])
    ->name('absensi');


Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

require __DIR__.'/auth.php';
