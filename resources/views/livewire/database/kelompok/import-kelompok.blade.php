<div class="mb-4">
    <form action="{{ route('import.kelompok') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-4 flex-wrap">
        @csrf
        <label class="flex items-center cursor-pointer">
            <input type="file" name="file" accept=".xlsx,.xls,.csv" class="hidden" id="fileInput-kelompok" onchange="document.getElementById('fileName-kelompok').textContent = this.files[0]?.name || 'Belum ada file dipilih'">
            <flux:button type="button" variant="filled" onclick="document.getElementById('fileInput-kelompok').click()">
                Pilih File
            </flux:button>
            <span class="ml-2 text-gray-700" id="fileName-kelompok">
                Belum ada file dipilih
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
