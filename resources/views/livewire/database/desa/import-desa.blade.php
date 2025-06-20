<div class="mb-4">
    <form wire:submit.prevent="import">
        <input type="file" wire:model="file" accept=".xlsx,.csv">
        @error('file') <span class="text-red-500">{{ $message }}</span> @enderror
        <flux:button type="submit" variant="filled" class="ml-2">Import Desa</flux:button>
        <a href="{{ asset('templates/template_desa.xlsx') }}" download>
    <flux:button type="button" variant="filled" class="ml-2">Download Template</flux:button>
        </a>
    </form>
    @if (session()->has('success'))
        <div class="text-green-600 mt-2">{{ session('success') }}</div>
    @endif
</div>
