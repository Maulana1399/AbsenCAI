<div class="space-y-6 px-4 lg:px-0">
    <div class="w-full">
        <flux:heading size="xl" level="1">{{ __('QR & Label') }}</flux:heading>
        <flux:subheading size="lg" class="mb-8">{{ __('Generate QR individual, batch export, dan preview label 4x4.') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <button
                type="button"
                wire:click="$set('mode', 'individual')"
                class="w-full rounded-xl px-4 py-3 text-sm font-medium transition {{ $mode === 'individual' ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-white text-zinc-700 ring-1 ring-zinc-200 hover:bg-zinc-50 dark:bg-zinc-900 dark:text-zinc-200 dark:ring-zinc-800 dark:hover:bg-zinc-800' }}"
            >
                {{ __('Generate Individual') }}
            </button>

            <button
                type="button"
                wire:click="$set('mode', 'batch')"
                class="w-full rounded-xl px-4 py-3 text-sm font-medium transition {{ $mode === 'batch' ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-white text-zinc-700 ring-1 ring-zinc-200 hover:bg-zinc-50 dark:bg-zinc-900 dark:text-zinc-200 dark:ring-zinc-800 dark:hover:bg-zinc-800' }}"
            >
                {{ __('Batch QR Export') }}
            </button>

            <button
                type="button"
                wire:click="$set('mode', 'label')"
                class="w-full rounded-xl px-4 py-3 text-sm font-medium transition {{ $mode === 'label' ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-white text-zinc-700 ring-1 ring-zinc-200 hover:bg-zinc-50 dark:bg-zinc-900 dark:text-zinc-200 dark:ring-zinc-800 dark:hover:bg-zinc-800' }}"
            >
                {{ __('Print Label') }}
            </button>
        </div>

        @if($mode === 'individual')
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="mb-4">
                        <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Search Participant</label>
                        <input
                            wire:model.live.debounce.300ms="search"
                            type="text"
                            class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                            placeholder="Nama / Participant Number / Attendance Code"
                        >
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Hasil Pencarian</h2>
                            <button type="button" wire:click="refreshBatchAndLabelPreview" class="rounded-lg bg-zinc-900 px-3 py-2 text-xs font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white">Refresh</button>
                        </div>

                        <div class="grid gap-3">
                            @forelse($results as $participant)
                                <button type="button" wire:click="selectParticipant({{ $participant->id }})" class="w-full rounded-xl border p-3 text-left transition hover:bg-zinc-50 dark:hover:bg-zinc-900 {{ $selectedParticipantId === $participant->id ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30' : 'border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950' }}">
                                    <div class="font-semibold text-zinc-900 dark:text-white">{{ $participant->person?->nama ?? '-' }}</div>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $participant->participant_number }} · {{ $participant->attendance_code }}</div>
                                </button>
                            @empty
                                <div class="rounded-xl border border-dashed border-zinc-200 p-4 text-sm text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">Belum ada hasil pencarian.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                    <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Detail Peserta</h2>

                    @if($selectedParticipant)
                        <div class="mt-4 space-y-3 text-sm text-zinc-700 dark:text-zinc-300">
                            <div class="rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-900"><span class="font-medium">Nama:</span> <span>{{ $selectedParticipant->person?->nama ?? '-' }}</span></div>
                            <div class="rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-900"><span class="font-medium">Participant Number:</span> <span>{{ $selectedParticipant->participant_number }}</span></div>
                            <div class="rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-900"><span class="font-medium">Attendance Code:</span> <span>{{ $selectedParticipant->attendance_code }}</span></div>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-2">
                            <button wire:click="downloadPng" type="button" class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">Download PNG</button>
                        </div>
                    @else
                        <div class="mt-4 rounded-xl border border-dashed border-zinc-200 p-4 text-sm text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">Pilih peserta untuk melihat QR.</div>
                    @endif
                </div>
            </div>
        @endif

        @if($mode === 'batch')
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Desa</label>
                        <select wire:model.live="filterDesa" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            <option value="">Semua</option>
                            @foreach($daftarDesa as $desa)
                                <option value="{{ $desa->id }}">{{ $desa->desa_asal }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Kelompok</label>
                        <select wire:model.live="filterKelompok" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            <option value="">Semua</option>
                            @foreach($daftarKelompok as $kelompok)
                                <option value="{{ $kelompok->id }}">{{ $kelompok->kelompok_asal }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Regu</label>
                        <select wire:model.live="filterRegu" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            <option value="">Semua</option>
                            @foreach($daftarRegu as $regu)
                                <option value="{{ $regu->id }}">{{ $regu->regu }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Gender</label>
                        <select wire:model.live="filterGender" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            <option value="">Semua</option>
                            <option value="Laki - Laki">Laki - Laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Keyword</label>
                        <input wire:model.live.debounce.300ms="filterKeyword" type="text" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" placeholder="Nama / Participant Number / Attendance Code">
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" wire:click="generateBatchExport" class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">Generate Export</button>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <button
                        type="button"
                        wire:click="printAllFiltered"
                        @disabled($batchTotal === 0)
                        class="inline-flex items-center rounded-xl bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 disabled:cursor-not-allowed disabled:bg-zinc-300 disabled:text-zinc-500 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white dark:disabled:bg-zinc-800 dark:disabled:text-zinc-500"
                    >
                        Print All Filtered
                    </button>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Generated</div>
                        <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $batchPreview['generated'] ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Skipped</div>
                        <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $batchPreview['skipped'] ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Failed</div>
                        <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ $batchPreview['failed'] ?? 0 }}</div>
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Participant Preview</h2>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Total: {{ $batchTotal }}</div>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @forelse($batchParticipants as $participant)
                            <button type="button" wire:click="selectParticipant({{ $participant->id }})" class="rounded-xl border p-3 text-left transition hover:bg-zinc-50 dark:hover:bg-zinc-800 {{ $selectedParticipantId === $participant->id ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30' : 'border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900' }}">
                                <div class="font-semibold text-zinc-900 dark:text-white">{{ $participant->person?->nama ?? '-' }}</div>
                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $participant->participant_number }} · {{ $participant->attendance_code }}</div>
                            </button>
                        @empty
                            <div class="rounded-xl border border-dashed border-zinc-200 p-4 text-sm text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">Tidak ada peserta untuk preview.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        @if($mode === 'label')
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <flux:heading size="lg" level="2">{{ __('Print Label 4x4') }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Reuses PrintEngine for filtered participants.') }}</flux:text>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_420px]">
                    <div class="space-y-3">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Desa</label>
                                <select wire:model.live="filterDesa" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                    <option value="">Semua</option>
                                    @foreach($daftarDesa as $desa)
                                        <option value="{{ $desa->id }}">{{ $desa->desa_asal }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Kelompok</label>
                                <select wire:model.live="filterKelompok" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                    <option value="">Semua</option>
                                    @foreach($daftarKelompok as $kelompok)
                                        <option value="{{ $kelompok->id }}">{{ $kelompok->kelompok_asal }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Regu</label>
                                <select wire:model.live="filterRegu" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                    <option value="">Semua</option>
                                    @foreach($daftarRegu as $regu)
                                        <option value="{{ $regu->id }}">{{ $regu->regu }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Gender</label>
                                <select wire:model.live="filterGender" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                    <option value="">Semua</option>
                                    <option value="Laki - Laki">Laki - Laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Keyword</label>
                                <input wire:model.live.debounce.300ms="filterKeyword" type="text" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" placeholder="Nama / Participant Number / Attendance Code">
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Preview List</div>
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">Total Peserta: {{ $labelPreview->count() }}</div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                            @forelse($labelPreview as $participant)
                                <button type="button" wire:click="selectLabelParticipant({{ $participant->id }})" class="cursor-pointer rounded-xl border p-3 text-left transition hover:bg-zinc-50 dark:hover:bg-zinc-800 {{ $selectedLabelParticipantId === $participant->id ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30' : 'border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900' }}">
                                    <div class="font-semibold text-zinc-900 dark:text-white">{{ $participant->person?->nama ?? '-' }}</div>
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $participant->participant_number }} · {{ $participant->attendance_code }}</div>
                                </button>
                            @empty
                                <div class="rounded-xl border border-dashed border-zinc-200 p-4 text-sm text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">Tidak ada peserta untuk preview.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Label Preview 4x4</div>
                            <div class="flex flex-wrap gap-2">
                                @if($selectedLabelParticipantId)
                                    <a
                                        href="{{ route('qr-label.print.selected', ['participant' => $selectedLabelParticipantId]) }}"
                                        target="_blank"
                                        class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-medium transition bg-blue-600 text-white hover:bg-blue-700"
                                    >
                                        Print Label
                                    </a>
                                @else
                                    <span
                                        class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-medium transition cursor-not-allowed bg-zinc-300 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-500"
                                    >
                                        Print Label
                                    </span>
                                @endif

                                @if($labelPreview->count() > 0)
                                    <a
                                        href="{{ route('qr-label.print.filtered', [
                                            'desa' => $filterDesa,
                                            'kelompok' => $filterKelompok,
                                            'regu' => $filterRegu,
                                            'gender' => $filterGender,
                                            'keyword' => $filterKeyword
                                        ]) }}"
                                        target="_blank"
                                        class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-medium transition border border-blue-600 bg-white text-blue-600 hover:bg-blue-50 dark:border-blue-500 dark:bg-zinc-950 dark:text-blue-400 dark:hover:bg-blue-950/30"
                                    >
                                        Print All Filtered
                                    </a>
                                    <a
                                        href="{{ route('qr-label.print.a4', [
                                            'desa' => $filterDesa,
                                            'kelompok' => $filterKelompok,
                                            'regu' => $filterRegu,
                                            'gender' => $filterGender,
                                            'keyword' => $filterKeyword
                                        ]) }}"
                                        target="_blank"
                                        class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-medium transition border border-blue-600 bg-white text-blue-600 hover:bg-blue-50 dark:border-blue-500 dark:bg-zinc-950 dark:text-blue-400 dark:hover:bg-blue-950/30"
                                    >
                                        Print All A4
                                    </a>
                                @else
                                    <span
                                        class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-medium transition cursor-not-allowed bg-zinc-300 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-500"
                                    >
                                        Print All Filtered
                                    </span>
                                    <span
                                        class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-medium transition cursor-not-allowed bg-zinc-300 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-500"
                                    >
                                        Print All A4
                                    </span>
                                @endif
                            </div>
                        </div>
                        @if($labelPreviewHtml)
                            <div class="flex justify-center overflow-auto rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800">
                                <iframe
                                    srcdoc="{!! htmlspecialchars($labelPreviewHtml, ENT_QUOTES, 'UTF-8') !!}"
                                    class="h-[4cm] w-[4cm] shrink-0 border-0 bg-white"
                                    title="Label Preview 4x4"
                                ></iframe>
                            </div>
                        @else
                            <div class="flex h-[4in] items-center justify-center rounded-xl border border-dashed border-zinc-200 p-4 text-sm text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">Tidak ada peserta untuk preview.</div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
