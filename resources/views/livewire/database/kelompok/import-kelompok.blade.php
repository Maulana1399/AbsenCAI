<div class="mb-4">
    <form wire:submit.prevent="import" class="flex items-center gap-4 flex-wrap">
        <label class="flex items-center cursor-pointer">
            <input type="file" wire:model="file" accept=".xlsx,.csv" class="hidden" id="fileInput">
            <flux:button type="button" variant="filled" onclick="document.getElementById('fileInput').click()">
                Pilih File
            </flux:button>
            <span class="ml-2 text-gray-700" id="fileName">
                {{ $file ? $file->getClientOriginalName() : 'Belum ada file dipilih' }}
            </span>
        </label>
        @error('file') <span class="text-red-500">{{ $message }}</span> @enderror

        <flux:button type="submit" variant="filled">Import Kelompok</flux:button>
        <a href="{{ asset('templates/template_kelompok.xlsx') }}" download>
            <flux:button type="button" variant="filled">Download Template</flux:button>
        </a>
    </form>
    @if (session()->has('success'))
        <div class="text-green-600 mt-2">{{ session('success') }}</div>
    @endif
</div>
