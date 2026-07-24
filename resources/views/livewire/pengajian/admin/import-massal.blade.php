<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Import Massal Peserta Pengajian</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Import peserta Pengajian Desa secara massal dari file CSV atau Excel.</p>
    </div>

    @if ($noActiveEvent)
        <div class="rounded-xl border border-dashed border-amber-300 bg-amber-50 p-8 text-center dark:border-amber-800 dark:bg-amber-950">
            <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                Tidak ada event aktif. Silakan pilih atau aktifkan event terlebih dahulu.
            </p>
            <a href="{{ route('events.index') }}" class="mt-4 inline-flex items-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">
                Kelola Event
            </a>
        </div>
    @else
        {{-- Step 1: Upload --}}
        @if ($step === 1)
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                    Upload File
                </h2>

                @if ($eventName)
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        Event aktif: <span class="font-medium text-zinc-900 dark:text-white">{{ $eventName }}</span>
                    </p>
                @endif

                <div class="mt-4 rounded-lg border border-dashed border-zinc-300 bg-zinc-50 p-6 text-center dark:border-zinc-700 dark:bg-zinc-900/50">
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Format File</p>
                    <p class="mt-1 text-xs text-zinc-400">
                        Kolom: <code class="rounded bg-zinc-200 px-1 py-0.5 text-xs dark:bg-zinc-700">nama</code>,
                        <code class="rounded bg-zinc-200 px-1 py-0.5 text-xs dark:bg-zinc-700">jenis_kelamin</code>,
                        <code class="rounded bg-zinc-200 px-1 py-0.5 text-xs dark:bg-zinc-700">tanggal_lahir</code>,
                        <code class="rounded bg-zinc-200 px-1 py-0.5 text-xs dark:bg-zinc-700">desa</code>,
                        <code class="rounded bg-zinc-200 px-1 py-0.5 text-xs dark:bg-zinc-700">kelompok</code>
                    </p>
                    <p class="mt-1 text-xs text-zinc-400">
                        Format tanggal: <code class="rounded bg-zinc-200 px-1 py-0.5 text-xs dark:bg-zinc-700">YYYY-MM-DD</code>.
                        Jenis kelamin: <code class="rounded bg-zinc-200 px-1 py-0.5 text-xs dark:bg-zinc-700">L</code> atau <code class="rounded bg-zinc-200 px-1 py-0.5 text-xs dark:bg-zinc-700">P</code>.
                    </p>

                    <div class="mt-4">
                        <a href="{{ route('pengajian.import-massal.template') }}" download>
                            <flux:button type="button" variant="ghost" icon-trailing="arrow-down-tray">
                                Download Template
                            </flux:button>
                        </a>
                    </div>

                    <div class="mt-4">
                        <input
                            type="file"
                            wire:model="file"
                            accept=".csv,.xlsx,.xls,.txt"
                            class="block w-full text-sm text-zinc-500 file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-950 dark:file:text-emerald-400"
                        />
                    </div>

                    @error('file')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6 flex gap-2">
                    <flux:button wire:click="preview" :loading="$processing" :disabled="!$file">
                        Preview & Validasi
                    </flux:button>
                </div>
            </div>
        @endif

        {{-- Step 2: Preview & Validation --}}
        @if ($step === 2)
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                    Preview Data
                </h2>

                @if (!empty($validationErrors))
                    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950">
                        <p class="text-sm font-medium text-red-700 dark:text-red-400">
                            {{ count($validationErrors) }} baris memiliki kesalahan validasi:
                        </p>
                        <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-600 dark:text-red-400">
                            @foreach ($validationErrors as $error)
                                <li>
                                    Baris {{ $error['row'] }}:
                                    @foreach ($error['errors'] as $msg)
                                        <span class="ml-1">{{ $msg }}</span>
                                    @endforeach
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950">
                        <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">
                            Validasi berhasil! {{ count($previewRows) }} baris siap diimport.
                        </p>
                    </div>
                @endif

                @if (!empty($previewRows))
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                    <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 uppercase">#</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 uppercase">Nama</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 uppercase">JK</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 uppercase">Tgl Lahir</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 uppercase">Desa</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 uppercase">Kelompok</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($previewRows as $index => $row)
                                    <tr class="border-b border-zinc-100 dark:border-zinc-800">
                                        <td class="px-3 py-2 text-zinc-400">{{ $index + 1 }}</td>
                                        <td class="px-3 py-2 text-zinc-900 dark:text-white">{{ $row['nama'] ?? '' }}</td>
                                        <td class="px-3 py-2 text-zinc-600">{{ $row['jenis_kelamin'] ?? '' }}</td>
                                        <td class="px-3 py-2 text-zinc-600">{{ $row['tanggal_lahir'] ?? '' }}</td>
                                        <td class="px-3 py-2 text-zinc-600">{{ $row['desa'] ?? '' }}</td>
                                        <td class="px-3 py-2 text-zinc-600">{{ $row['kelompok'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="mt-6 flex gap-2">
                    @if (empty($validationErrors) && !empty($previewRows))
                        <flux:button wire:click="executeImport" variant="primary" :loading="$processing">
                            Import {{ count($previewRows) }} Data
                        </flux:button>
                    @endif
                    <flux:button wire:click="resetImport" variant="ghost">
                        Upload Ulang
                    </flux:button>
                </div>
            </div>
        @endif

        {{-- Step 3: Result --}}
        @if ($step === 3)
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                    Hasil Import
                </h2>

                @if (isset($importResult['error']))
                    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950">
                        <p class="text-sm font-medium text-red-700 dark:text-red-400">{{ $importResult['error'] }}</p>
                    </div>
                @else
                    <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-center dark:border-emerald-900 dark:bg-emerald-950">
                            <p class="text-xl font-bold text-emerald-700 dark:text-emerald-400">{{ $importResult['created_persons'] ?? 0 }}</p>
                            <p class="text-xs text-emerald-600 dark:text-emerald-500">Person Baru</p>
                        </div>
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-center dark:border-blue-900 dark:bg-blue-950">
                            <p class="text-xl font-bold text-blue-700 dark:text-blue-400">{{ $importResult['matched_persons'] ?? 0 }}</p>
                            <p class="text-xs text-blue-600 dark:text-blue-500">Person Cocok</p>
                        </div>
                        <div class="rounded-lg border border-purple-200 bg-purple-50 p-3 text-center dark:border-purple-900 dark:bg-purple-950">
                            <p class="text-xl font-bold text-purple-700 dark:text-purple-400">{{ $importResult['created_participations'] ?? 0 }}</p>
                            <p class="text-xs text-purple-600 dark:text-purple-500">Partisipasi Baru</p>
                        </div>
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-center dark:border-amber-900 dark:bg-amber-950">
                            <p class="text-xl font-bold text-amber-700 dark:text-amber-400">{{ $importResult['skipped_duplicates'] ?? 0 }}</p>
                            <p class="text-xs text-amber-600 dark:text-amber-500">Duplikat Dilewati</p>
                        </div>
                        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-center dark:border-red-900 dark:bg-red-950">
                            <p class="text-xl font-bold text-red-700 dark:text-red-400">{{ $importResult['failed_rows'] ?? 0 }}</p>
                            <p class="text-xs text-red-600 dark:text-red-500">Gagal</p>
                        </div>
                    </div>

                    @if (!empty($importResult['errors']))
                        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950">
                            <p class="text-sm font-medium text-red-700 dark:text-red-400">
                                {{ count($importResult['errors']) }} baris gagal:
                            </p>
                            <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-600 dark:text-red-400">
                                @foreach ($importResult['errors'] as $error)
                                    <li>Baris {{ $error['row'] }}: {{ $error['message'] }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endif

                <div class="mt-6 flex gap-2">
                    <flux:button wire:click="resetImport">
                        Import Lagi
                    </flux:button>
                    <flux:button variant="ghost" href="{{ route('pengajian.report') }}" wire:navigate>
                        Lihat Regional Report
                    </flux:button>
                </div>
            </div>
        @endif
    @endif
</div>
