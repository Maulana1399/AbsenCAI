<div>
    <flux:modal.trigger :name="'import-'.$definitionKey">
        <flux:button variant="filled">{{ $meta['displayName'] }}</flux:button>
    </flux:modal.trigger>

    <flux:modal :name="'import-'.$definitionKey" class="md:max-w-3xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $meta['displayName'] }}</flux:heading>
                <flux:subheading size="sm">{{ $meta['description'] }}</flux:subheading>
            </div>

            {{-- Step 1: Upload (+ parameters from definition) --}}
            @if ($step === 1)
                <x-import.wizard title="Upload" :step="1" :steps="$steps">
                    <x-import.progress :processing="$processing" message="Membaca dan memvalidasi file…" />

                    @foreach ($meta['parameters'] as $key => $spec)
                        @if (($spec['type'] ?? 'text') === 'select')
                            <div class="mt-4">
                                <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $spec['label'] }} @if ($spec['required'] ?? false)<span class="text-red-500">*</span>@endif
                                </label>
                                <flux:select wire:model="parameters.{{ $key }}" placeholder="Pilih {{ $spec['label'] }}">
                                    <flux:select.option value="">-- Pilih {{ $spec['label'] }} --</flux:select.option>
                                    @foreach ($meta['parameterOptions'][$key] ?? [] as $value => $label)
                                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                @error('parameters.'.$key) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    @endforeach

                    <x-import.upload-section
                        wire:model="file"
                        :file="$file"
                        :upload-error="$uploadError"
                        accept=".csv,.xlsx,.xls,.txt"
                        :template-url="$templateUrl"
                    >
                        <x-slot:hint>
                            Kolom:
                            @foreach ($meta['columns'] as $key => $col)
                                <code class="rounded bg-zinc-200 px-1 py-0.5 text-xs dark:bg-zinc-700">{{ $key }}</code>@if (! $loop->last),@endif
                            @endforeach
                            . Periksa sheet PETUNJUK pada template untuk format dan aturan duplicate.
                        </x-slot:hint>
                    </x-import.upload-section>

                    <div class="mt-6 flex gap-2">
                        <flux:button wire:click="preview" :loading="$processing" :disabled="$processing || !$file">
                            Preview & Validasi
                        </flux:button>
                    </div>
                </x-import.wizard>
            @endif

            {{-- Step 2: Preview --}}
            @if ($step === 2)
                <x-import.wizard title="Preview" :step="2" :steps="$steps">
                    <x-import.preview-table :rows="$previewRows" :columns="array_map(fn ($col) => $col['label'], $meta['columns'])" />

                    <div class="mt-6 flex gap-2">
                        <flux:button wire:click="goToImport" variant="primary">
                            Lanjut ke Import
                        </flux:button>
                        <flux:button wire:click="resetImport" variant="ghost">
                            Upload Ulang
                        </flux:button>
                    </div>
                </x-import.wizard>
            @endif

            {{-- Step 3: Validation --}}
            @if ($step === 3)
                <x-import.wizard title="Validation" :step="3" :steps="$steps">
                    <x-import.validation-errors :errors="$validationErrors" />

                    <div class="mt-6 flex gap-2">
                        <flux:button wire:click="resetImport" variant="ghost">
                            Upload Ulang
                        </flux:button>
                    </div>
                </x-import.wizard>
            @endif

            {{-- Step 4: Import (confirmation) --}}
            @if ($step === 4)
                <x-import.wizard title="Import" :step="4" :steps="$steps">
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <x-import.summary-card label="Total" :value="$summary['total'] ?? 0" tone="zinc" />
                        <x-import.summary-card label="Valid" :value="$summary['valid'] ?? 0" tone="emerald" />
                        <x-import.summary-card label="Invalid" :value="$summary['invalid'] ?? 0" tone="red" />
                        <x-import.summary-card label="Duplicate" :value="$summary['duplicate'] ?? 0" tone="amber" />
                        <x-import.summary-card label="Warning" :value="$summary['warning'] ?? 0" tone="purple" />
                        <x-import.summary-card label="Akan Dibuat" :value="$summary['will_create'] ?? 0" tone="blue" />
                    </div>

                    <div class="mt-6 flex gap-2">
                        <flux:button wire:click="executeImport" variant="primary" :loading="$processing" :disabled="$processing">
                            Import {{ $summary['will_create'] ?? 0 }} Data
                        </flux:button>
                        <flux:button wire:click="resetImport" variant="ghost" :disabled="$processing">
                            Upload Ulang
                        </flux:button>
                    </div>
                </x-import.wizard>
            @endif

            {{-- Step 5: Result --}}
            @if ($step === 5)
                <x-import.wizard title="Hasil Import" :step="5" :steps="$steps">
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
