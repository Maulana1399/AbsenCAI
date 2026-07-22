<div>
    <flux:modal.trigger name="create-surat-izin">
        <flux:button variant="primary">{{ __('Buat Surat Izin') }}</flux:button>
    </flux:modal.trigger>

    <flux:modal name="create-surat-izin" class="md:w-[32rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Buat Surat Izin') }}</flux:heading>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Cari Peserta') }}</label>
                <input wire:model.live.debounce.300ms="searchPeserta" type="text"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                    placeholder="{{ __('Nama / NIP / Attendance Code') }}">

                @if(strlen($searchPeserta) >= 2 && count($results) > 0)
                    <div class="mt-2 max-h-40 overflow-y-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                        @foreach($results as $p)
                            <button type="button" wire:click="selectPeserta({{ $p->id }}, '{{ $p->source }}')"
                                class="w-full px-3 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800">
                                <span>{{ $p->nama }}</span>
                                @if($p->source === 'canonical')
                                    <span class="ml-1 text-xs text-blue-500">Person</span>
                                @endif
                                <span class="ml-1 text-xs text-zinc-400">{{ $p->nip ?? '-' }}</span>
                            </button>
                        @endforeach
                    </div>
                @elseif(strlen($searchPeserta) >= 2)
                    <p class="mt-1 text-xs text-zinc-500">{{ __('Tidak ada peserta ditemukan.') }}</p>
                @endif

                @error('selectedPesertaId')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror

                @if($selectedPesertaId)
                    <div class="mt-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-950/30 dark:text-blue-300">
                        {{ __('Dipilih:') }} <strong>{{ $selectedPesertaNama }}</strong>
                        <button type="button" wire:click="$set('selectedPesertaId', null)" class="ml-2 text-blue-600 hover:text-blue-800 dark:text-blue-400">×</button>
                    </div>
                @endif
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Alasan') }}</label>
                <textarea wire:model="alasan"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                    placeholder="{{ __('Masukkan alasan izin...') }}" rows="3"></textarea>
                @error('alasan') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Jenis Izin') }}</label>
                <div class="flex gap-3">
                    <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <input type="radio" wire:model="jenisIzin" value="pulang" class="text-blue-600 focus:ring-blue-500">
                        {{ __('Pulang') }}
                    </label>
                    <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <input type="radio" wire:model="jenisIzin" value="keluar" class="text-blue-600 focus:ring-blue-500">
                        {{ __('Keluar') }}
                    </label>
                </div>
                @error('jenisIzin') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Tanggal Mulai') }}</label>
                    <input wire:model="tanggal_mulai" type="date"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    @error('tanggal_mulai') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Tanggal Selesai') }}</label>
                    <input wire:model="tanggal_selesai" type="date"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    @error('tanggal_selesai') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>
                <flux:spacer />
                <flux:button wire:click="saveDraft" wire:loading.attr="disabled" variant="ghost">
                    {{ __('Simpan Draft') }}
                </flux:button>
                <flux:button wire:click="saveAndSubmit" wire:loading.attr="disabled" variant="primary">
                    {{ __('Simpan & Submit') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
