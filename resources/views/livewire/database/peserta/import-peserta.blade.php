<div class="mb-4">
    <form action="{{ route('import.peserta') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-4 flex-wrap">
        @csrf
        <div class="w-full text-sm text-zinc-500 dark:text-zinc-400">
            NIP dan regu akan diisi otomatis saat import.
        </div>
        <label class="flex items-center cursor-pointer">
            <input type="file" name="file" accept=".xlsx,.xls,.csv" class="hidden" id="fileInput-peserta" onchange="document.getElementById('fileName-peserta').textContent = this.files[0]?.name || 'Belum ada file dipilih'">
            <flux:button type="button" variant="filled" onclick="document.getElementById('fileInput-peserta').click()">
                Pilih File
            </flux:button>
            <span class="ml-2 text-gray-700" id="fileName-peserta">
                Belum ada file dipilih
            </span>
        </label>
        @error('file') <span class="text-red-500">{{ $message }}</span> @enderror

        <flux:button type="submit" variant="filled">Import Peserta</flux:button>
        <a href="{{ asset('templates/template_peserta.xlsx') }}" download>
            <flux:button type="button" variant="filled">Download Template</flux:button>
        </a>
    </form>
    @if (session()->has('success'))
        <div class="text-green-600 mt-2">{{ session('success') }}</div>
    @endif
</div>
