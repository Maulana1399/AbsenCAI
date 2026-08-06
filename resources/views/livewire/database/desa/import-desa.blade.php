<div>
    <flux:modal.trigger name="import-desa">
        <flux:button variant="filled">Import Desa</flux:button>
    </flux:modal.trigger>

    <flux:modal name="import-desa" class="md:max-w-3xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Import Desa</flux:heading>
                <flux:subheading size="sm">Import data desa dari file CSV atau Excel.</flux:subheading>
            </div>

            {{-- Step 1: Upload --}}
            @if ($step === 1)
                <x-import.wizard title="Upload File" :step="1" :steps="['Upload', 'Preview & Validasi', 'Hasil']">
                    <x-import.progress :processing="$processing" message="Membaca dan memvalidasi file…" />

                    <x-import.upload-section
                        wire:model="file"
                        :file="$file"
                        :upload-error="$uploadError"
                        accept=".csv,.xlsx,.xls,.txt"
                        :template-url="route('import.desa.template')"
                    >
                        <x-slot:hint>
                            Kolom: <code class="rounded bg-zinc-200 px-1 py-0.5 text-xs dark:bg-zinc-700">desa</code>.
                            Setiap baris berisi satu nama desa. Nama desa yang sudah ada akan dilewati (duplikat).
                        </x-slot:hint>
                    </x-import.upload-section>

                    <div class="mt-6 flex gap-2">
                        <flux:button wire:click="preview" :loading="$processing" :disabled="$processing || !$file">
                            Preview & Validasi
                        </flux:button>
                    </div>
                </x-import.wizard>
            @endif

            {{-- Step 2: Preview & Validation --}}
            @if ($step === 2)
                <x-import.wizard title="Preview & Validasi" :step="2" :steps="['Upload', 'Preview & Validasi', 'Hasil']">
                    <x-import.progress :processing="$processing" message="Mengimpor data…" />

                    @if (! empty($validationErrors))
                        <x-import.validation-errors :errors="$validationErrors" />
                    @elseif (empty($previewRows))
                        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950">
                            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">
                                File tidak berisi data yang bisa diimport.
                            </p>
                        </div>
                    @else
                        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950">
                            <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">
                                Validasi berhasil! {{ count($previewRows) }} baris siap diimport.
                            </p>
                        </div>
                    @endif

                    <x-import.preview-table :rows="$previewRows" :columns="['desa' => 'Desa']" />

                    <div class="mt-6 flex gap-2">
                        @if (empty($validationErrors) && ! empty($previewRows))
                            <flux:button wire:click="executeImport" variant="primary" :loading="$processing" :disabled="$processing">
                                Import {{ count($previewRows) }} Data
                            </flux:button>
                        @endif
                        <flux:button wire:click="resetImport" variant="ghost" :disabled="$processing">
                            Upload Ulang
                        </flux:button>
                    </div>
                </x-import.wizard>
            @endif

            {{-- Step 3: Result --}}
            @if ($step === 3)
                <x-import.wizard title="Hasil Import" :step="3" :steps="['Upload', 'Preview & Validasi', 'Hasil']">
                    <x-import.result-card :result="$importResult" />

                    <div class="mt-6 flex gap-2">
                        <flux:button wire:click="resetImport">
                            Import Lagi
                        </flux:button>
                    </div>
                </x-import.wizard>
            @endif
        </div>
    </flux:modal>
</div>
