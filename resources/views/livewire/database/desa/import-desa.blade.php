<div class="mb-4">
    <form action="{{ route('import.desa') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-4 flex-wrap">
        @csrf
        <label class="flex items-center cursor-pointer">
            <input type="file" name="file" accept=".xlsx,.xls,.csv" class="hidden" id="fileInput-desa" onchange="document.getElementById('fileName-desa').textContent = this.files[0]?.name || 'Belum ada file dipilih'">
            <flux:button type="button" variant="filled" onclick="document.getElementById('fileInput-desa').click()">
                Pilih File
            </flux:button>
            <span class="ml-2 text-gray-700" id="fileName-desa">
                Belum ada file dipilih
            </span>
        </label>
        @error('file') <span class="text-red-500">{{ $message }}</span> @enderror

        <flux:button type="submit" variant="filled">Import Desa</flux:button>
        <a href="{{ asset('templates/template_desa.xlsx') }}" download>
            <flux:button type="button" variant="filled">Download Template</flux:button>
        </a>
    </form>
    @if (session()->has('success'))
        <div class="text-green-600 mt-2">{{ session('success') }}</div>
    @endif
</div>
