<x-layouts.auth.simple>
    @php($registrasi = session('self_register', []))

    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <div class="space-y-3 text-center">
            <flux:heading size="xl">Terima kasih telah melakukan registrasi.</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                Silakan datang saat registrasi ulang.
            </flux:text>
        </div>

        <div class="mt-6 space-y-3 rounded-xl bg-zinc-50 p-4 text-sm dark:bg-zinc-900/60">
            <div><span class="font-medium">Nama:</span> {{ $registrasi['nama'] ?? '-' }}</div>
            <div><span class="font-medium">NIP:</span> {{ $registrasi['nip'] ?? '-' }}</div>
            <div><span class="font-medium">Desa:</span> {{ $registrasi['desa'] ?? '-' }}</div>
            <div><span class="font-medium">Kelompok:</span> {{ $registrasi['kelompok'] ?? '-' }}</div>
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <flux:link :href="route('registrasi.self')" class="w-full sm:w-auto">Daftar lagi</flux:link>
            <flux:link :href="route('dashboard')" class="w-full sm:w-auto">Kembali ke beranda</flux:link>
        </div>
    </div>
</x-layouts.auth.simple>
