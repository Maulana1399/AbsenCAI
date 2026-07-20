<div class="flex flex-col gap-6">
    @if ($errorMessage && $step !== 5)
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
            {{ $errorMessage }}
        </div>
    @endif

    @if ($eventName && $desaName)
        <div class="text-center mb-2">
            <div class="mx-auto h-16 w-16 rounded-full bg-emerald-600 flex items-center justify-center text-2xl font-bold text-white shadow-xl">
                KJA
            </div>

            <h1 class="mt-4 text-xl font-bold text-zinc-900 dark:text-white">
                {{ $eventName }}
            </h1>

            <p class="text-emerald-600 mt-1">
                {{ $desaName }}
            </p>
        </div>
    @endif

    {{-- STEP 1: Search --}}
    @if ($step === 1 && ! $attendanceDone)
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                Cari Nama
            </h2>

            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                Masukkan minimal 3 karakter nama Anda untuk mencari data kehadiran.
            </p>

            <div class="mt-4 flex gap-2">
                <flux:input
                    wire:model="query"
                    placeholder="Nama Anda"
                    class="flex-1"
                    autocomplete="off"
                />
                <flux:button
                    wire:click="search"
                    variant="primary"
                    :loading="$searching"
                >
                    Cari
                </flux:button>
            </div>

            @if (count($searchResults) > 0)
                <div class="mt-4 space-y-2">
                    @foreach ($searchResults as $result)
                        <button
                            wire:click="selectPerson({{ $result['id'] }})"
                            class="w-full rounded-lg border border-zinc-200 bg-white p-3 text-left text-sm hover:border-emerald-400 hover:bg-emerald-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-emerald-600 dark:hover:bg-emerald-950"
                        >
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $result['nama'] }}</span>
                            @if ($result['has_birth_date'])
                                <span class="ml-2 text-xs text-zinc-400">({{ $result['birth_date_masked'] }})</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            @elseif ($query !== '' && ! $searching)
                <p class="mt-3 text-sm text-zinc-400">Tidak ditemukan data dengan nama tersebut.</p>
            @endif
        </div>
    @endif

    {{-- STEP 3: Birth date verification --}}
    @if ($step === 3 && $selectedPersonName)
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                Verifikasi Tanggal Lahir
            </h2>

            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-white">
                {{ $selectedPersonName }}
            </p>

            <p class="mt-1 text-xs text-zinc-400">
                Masukkan tanggal lahir Anda untuk verifikasi (format: YYYY-MM-DD).
            </p>

            <div class="mt-4 flex gap-2">
                <flux:input
                    wire:model="birthDate"
                    placeholder="Contoh: 2000-01-15"
                    class="flex-1"
                    autocomplete="off"
                />
                <flux:button
                    wire:click="verifyBirthDate"
                    variant="primary"
                    :loading="$processing"
                >
                    Verifikasi
                </flux:button>
            </div>

            <div class="mt-3 flex gap-2">
                <button
                    wire:click="proceedWithoutBirthDate"
                    type="button"
                    class="text-xs text-zinc-400 underline hover:text-zinc-600 dark:hover:text-zinc-300"
                >
                    Data tanggal lahir tidak sesuai? Lanjutkan tanpa verifikasi
                </button>
            </div>
        </div>
    @endif

    {{-- STEP 4: Confirmation --}}
    @if ($step === 4 && $selectedPersonName)
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                Konfirmasi Data
            </h2>

            <div class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400">Nama</span>
                    <span class="font-medium text-zinc-900 dark:text-white">{{ $selectedPersonName }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400">Desa</span>
                    <span class="font-medium text-zinc-900 dark:text-white">{{ $desaName }}</span>
                </div>
                @if ($selectedPersonHasBirthDate && $birthDateVerified)
                    <div class="flex justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Tanggal Lahir</span>
                        <span class="inline-flex items-center gap-1 text-emerald-600">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                            Terverifikasi
                        </span>
                    </div>
                @elseif ($verificationSkipped)
                    <div class="flex justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Verifikasi</span>
                        <span class="text-xs text-zinc-400">Dilewati</span>
                    </div>
                @endif
            </div>

            <div class="mt-6 flex flex-col gap-3">
                <flux:button
                    wire:click="confirmAttendance"
                    variant="primary"
                    class="w-full"
                    :loading="$processing"
                >
                    Hadir
                </flux:button>

                <button
                    wire:click="resetSearch"
                    type="button"
                    class="text-sm text-zinc-400 underline hover:text-zinc-600 dark:hover:text-zinc-300"
                >
                    Bukan saya? Cari ulang
                </button>
            </div>
        </div>

        {{-- Correction --}}
        @if (! $correctionSubmitted)
            <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-900/50">
                <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Data saya tidak sesuai
                </h3>
                <p class="mt-1 text-xs text-zinc-400">
                    Laporkan jika data nama atau tanggal lahir Anda tidak sesuai.
                </p>

                <div class="mt-3 flex gap-2">
                    <flux:input
                        wire:model="correctionReason"
                        placeholder="Jelaskan perbedaan data Anda"
                        class="flex-1"
                        autocomplete="off"
                    />
                    <flux:button
                        wire:click="submitCorrection"
                        variant="ghost"
                        :loading="$processing"
                    >
                        Kirim
                    </flux:button>
                </div>
            </div>
        @else
            <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800 dark:border-yellow-800 dark:bg-yellow-950 dark:text-yellow-200">
                Laporan koreksi telah dikirim dan akan ditinjau oleh operator.
            </div>
        @endif
    @endif

    {{-- STEP 5: Result --}}
    @if ($step === 5 && $attendanceDone)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-6 text-center dark:border-emerald-800 dark:bg-emerald-950">
            <div class="mx-auto h-12 w-12 rounded-full bg-emerald-600 flex items-center justify-center text-xl text-white shadow-md">
                ✓
            </div>

            @if ($attendanceAlreadyExists)
                <h2 class="mt-4 text-lg font-bold text-zinc-900 dark:text-white">
                    Anda sudah tercatat hadir.
                </h2>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    Kehadiran Anda sudah tercatat sebelumnya.
                </p>
            @else
                <h2 class="mt-4 text-lg font-bold text-zinc-900 dark:text-white">
                    Kehadiran berhasil dicatat!
                </h2>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    Selamat mengikuti {{ $eventName }}.
                </p>
            @endif
        </div>

        <div class="text-center">
            <flux:button
                wire:click="resetSearch"
                variant="primary"
            >
                Absen untuk peserta lain
            </flux:button>
        </div>
    @endif

    {{-- Error state (nonce invalid, etc) --}}
    @if ($errorMessage && $step === 1 && ! $eventName)
        <div class="rounded-xl border border-red-200 bg-red-50 p-6 text-center dark:border-red-800 dark:bg-red-950">
            <p class="text-sm text-red-700 dark:text-red-300">
                {{ $errorMessage }}
            </p>
            <p class="mt-2 text-xs text-zinc-400">
                Silakan scan QR ulang atau hubungi operator desa.
            </p>
        </div>
    @endif
</div>
