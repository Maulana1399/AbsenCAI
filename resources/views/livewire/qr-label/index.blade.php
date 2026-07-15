<div class="space-y-6">
    <div class="flex flex-col gap-4">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">QR & Label</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Generate QR individual, batch export, dan label 4x4.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="$set('mode', 'individual')" class="rounded-full px-4 py-2 text-sm font-medium {{ $mode === 'individual' ? 'bg-blue-600 text-white' : 'bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:text-white' }}">Generate Individual QR</button>
            <button type="button" wire:click="$set('mode', 'batch')" class="rounded-full px-4 py-2 text-sm font-medium {{ $mode === 'batch' ? 'bg-blue-600 text-white' : 'bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:text-white' }}">Batch QR Export</button>
            <button type="button" wire:click="$set('mode', 'label')" class="rounded-full px-4 py-2 text-sm font-medium {{ $mode === 'label' ? 'bg-blue-600 text-white' : 'bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:text-white' }}">Print Label</button>
        </div>
    </div>

    @if($mode === 'individual')
        <div class="grid gap-4 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium">Search Participant</label>
                    <input wire:model.live.debounce.300ms="search" type="text" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900" placeholder="Nama / Participant Number / Attendance Code">
                </div>
                <div class="flex items-end">
                    <button type="button" wire:click="refreshBatchAndLabelPreview" class="rounded-xl bg-zinc-900 px-4 py-2 text-white dark:bg-zinc-100 dark:text-zinc-900">Refresh</button>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <div class="rounded-xl border border-zinc-200 p-3 dark:border-zinc-800">
                    <div class="text-sm text-zinc-500">Hasil Pencarian</div>
                    <div class="mt-3 space-y-2">
                        @forelse($results as $participant)
                            <button type="button" wire:click="selectParticipant({{ $participant->id }})" class="block w-full rounded-lg border px-3 py-2 text-left hover:bg-zinc-50 dark:hover:bg-zinc-900 {{ $selectedParticipantId === $participant->id ? 'border-blue-500' : 'border-zinc-200 dark:border-zinc-800' }}">
                                <div class="font-semibold">{{ $participant->nama }}</div>
                                <div class="text-xs text-zinc-500">{{ $participant->participant_number }} · {{ $participant->attendance_code }}</div>
                            </button>
                        @empty
                            <div class="text-sm text-zinc-500">Belum ada hasil pencarian.</div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 p-3 dark:border-zinc-800">
                    <div class="text-sm text-zinc-500">Detail Peserta</div>
                    @if($selectedParticipant)
                        <div class="mt-3 space-y-2 text-sm">
                            <div><span class="font-medium">Nama:</span> {{ $selectedParticipant->nama }}</div>
                            <div><span class="font-medium">Participant Number:</span> {{ $selectedParticipant->participant_number }}</div>
                            <div><span class="font-medium">Attendance Code:</span> {{ $selectedParticipant->attendance_code }}</div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <button wire:click="downloadPng" type="button" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white">Download PNG</button>
                            <button wire:click="downloadSvg" type="button" class="rounded-xl bg-zinc-900 px-4 py-2 text-sm font-medium text-white">Download SVG</button>
                        </div>
                    @else
                        <div class="mt-3 text-sm text-zinc-500">Pilih peserta untuk melihat QR.</div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($mode === 'batch')
        <div class="grid gap-4 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label class="mb-2 block text-sm font-medium">Desa</label>
                    <select wire:model.live="filterDesa" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                        <option value="">Semua</option>
                        @foreach($daftarDesa as $desa)
                            <option value="{{ $desa->id }}">{{ $desa->desa_asal }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium">Kelompok</label>
                    <select wire:model.live="filterKelompok" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                        <option value="">Semua</option>
                        @foreach($daftarKelompok as $kelompok)
                            <option value="{{ $kelompok->id }}">{{ $kelompok->kelompok_asal }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium">Regu</label>
                    <select wire:model.live="filterRegu" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                        <option value="">Semua</option>
                        @foreach($daftarRegu as $regu)
                            <option value="{{ $regu->id }}">{{ $regu->regu }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium">Gender</label>
                    <select wire:model.live="filterGender" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                        <option value="">Semua</option>
                        <option value="Laki - Laki">Laki - Laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium">Keyword</label>
                    <input wire:model.live.debounce.300ms="filterKeyword" type="text" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900" placeholder="Nama / Participant Number / Attendance Code">
                </div>
            </div>

            <div class="flex gap-2">
                <button type="button" wire:click="generateBatchExport" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white">Generate Export</button>
                <button type="button" wire:click="refreshBatchAndLabelPreview" class="rounded-xl bg-zinc-900 px-4 py-2 text-sm font-medium text-white">Refresh Preview</button>
            </div>

            <div class="grid gap-3 md:grid-cols-3 text-sm">
                <div class="rounded-xl border p-3">Generated: {{ $batchPreview['generated'] ?? 0 }}</div>
                <div class="rounded-xl border p-3">Skipped: {{ $batchPreview['skipped'] ?? 0 }}</div>
                <div class="rounded-xl border p-3">Failed: {{ $batchPreview['failed'] ?? 0 }}</div>
            </div>
        </div>
    @endif

    @if($mode === 'label')
        <div class="grid gap-4 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">Print Label 4x4</h2>
                    <p class="text-sm text-zinc-500">Reuses PrintEngine for filtered participants.</p>
                </div>
                <button type="button" wire:click="refreshBatchAndLabelPreview" class="rounded-xl bg-zinc-900 px-4 py-2 text-sm font-medium text-white">Refresh Preview</button>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-3">
                    <div class="text-sm text-zinc-500">Preview List</div>
                    @forelse($labelPreview as $participant)
                        <div class="rounded-xl border border-zinc-200 p-3 dark:border-zinc-800">
                            <div class="font-semibold">{{ $participant->nama }}</div>
                            <div class="text-xs text-zinc-500">{{ $participant->participant_number }} · {{ $participant->attendance_code }}</div>
                        </div>
                    @empty
                        <div class="text-sm text-zinc-500">Tidak ada peserta untuk preview.</div>
                    @endforelse
                </div>

                <div class="rounded-xl border border-zinc-200 p-3 dark:border-zinc-800">
                    <div class="text-sm text-zinc-500 mb-3">Label Preview</div>
                    @if($labelPreviewHtml)
                        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white p-2">
                            {!! $labelPreviewHtml !!}
                        </div>
                    @else
                        <div class="text-sm text-zinc-500">Tidak ada peserta untuk preview.</div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
