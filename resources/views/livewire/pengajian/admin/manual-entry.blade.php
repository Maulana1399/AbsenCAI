<div class="flex flex-col gap-6">
    <flux:heading size="xl">Tambah Peserta ke Event</flux:heading>

    {{-- Step 1: Form --}}
    @if ($step === 1)
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            @if ($errorMessage)
                <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-950 dark:text-red-400">
                    {{ $errorMessage }}
                </div>
            @endif

            <form wire:submit="submit" class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Event</label>
                    <select
                        wire:model="eventId"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                        required
                    >
                        <option value="">-- Pilih Event --</option>
                        @foreach ($events as $event)
                            <option value="{{ $event->id }}">{{ $event->name }}</option>
                        @endforeach
                    </select>
                    @error('eventId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Desa</label>
                    <select
                        wire:model.live="desaId"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                        required
                    >
                        <option value="">-- Pilih Desa --</option>
                        @foreach ($desas as $desa)
                            <option value="{{ $desa->id }}">{{ $desa->desa_asal }}</option>
                        @endforeach
                    </select>
                    @error('desaId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <hr class="border-zinc-200 dark:border-zinc-700">

                <flux:input
                    wire:model="nama"
                    label="Nama Lengkap"
                    type="text"
                    required
                    autofocus
                    placeholder="Masukkan nama lengkap"
                />

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Jenis Kelamin</label>
                    <select
                        wire:model="jenisKelamin"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                        required
                    >
                        <option value="">-- Pilih Jenis Kelamin --</option>
                        <option value="L">Laki - Laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                    @error('jenisKelamin') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <flux:input
                    wire:model="tanggalLahir"
                    label="Tanggal Lahir"
                    type="date"
                    required
                    placeholder=""
                />

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Kelompok</label>
                    <select
                        wire:model="kelompokId"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                        required
                    >
                        <option value="">-- Pilih Kelompok --</option>
                        @foreach ($kelompoks as $kelompok)
                            <option value="{{ $kelompok->id }}">{{ $kelompok->kelompok_asal }}</option>
                        @endforeach
                    </select>
                    @error('kelompokId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="pt-2">
                    <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled" wire:target="submit">
                        Daftarkan Peserta
                    </flux:button>
                </div>
            </form>
        </div>
    @endif

    {{-- Step 2: Confirmation --}}
    @if ($step === 2)
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                Verifikasi Data
            </h2>
            <p class="mt-1 text-xs text-zinc-400">
                Ditemukan data dengan nama yang mirip. Apakah ini orang yang sama?
            </p>

            <div class="mt-4 space-y-3">
                @foreach ($potentialMatches as $match)
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $match['nama'] }}</p>
                        <p class="text-sm text-zinc-500">
                            @if ($match['tanggal_lahir'])
                                Lahir: {{ $match['tanggal_lahir'] }}
                            @else
                                Tanggal lahir tidak tersedia
                            @endif
                        </p>
                        <div class="mt-3 flex gap-2">
                            <flux:button
                                wire:click="confirmMatch({{ $match['id'] }})"
                                size="sm"
                                :loading="$processing"
                            >
                                Ya, Ini Orangnya
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 rounded-lg border border-dashed border-zinc-300 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Jika tidak ada yang cocok, daftarkan sebagai peserta baru.
                </p>
                <div class="mt-3">
                    <flux:button
                        wire:click="createNewPerson"
                        variant="ghost"
                        size="sm"
                        :loading="$processing"
                    >
                        Daftarkan sebagai Peserta Baru
                    </flux:button>
                </div>
            </div>

            <div class="mt-4">
                <flux:button wire:click="resetForm" variant="ghost" class="w-full">
                    &larr; Kembali
                </flux:button>
            </div>
        </div>
    @endif

    {{-- Step 3: Success --}}
    @if ($step === 3)
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-center space-y-2">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900">
                    <svg class="h-7 w-7 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </div>
                <flux:heading size="lg">Peserta Berhasil Ditambahkan</flux:heading>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $successMessage }}</p>
            </div>

            <div class="mt-4 space-y-3 rounded-xl bg-zinc-50 p-4 text-sm dark:bg-zinc-900/60">
                <div><span class="font-medium">Nama:</span> {{ $resultPersonName }}</div>
                @if ($resultParticipantNumber)
                    <div><span class="font-medium">No. Peserta:</span> {{ $resultParticipantNumber }}</div>
                @endif
            </div>

            <div class="mt-6">
                <flux:button wire:click="resetForm" variant="primary" class="w-full">
                    Tambah Peserta Lagi
                </flux:button>
            </div>
        </div>
    @endif
</div>
