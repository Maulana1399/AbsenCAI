<div class="flex flex-col gap-6">
    <div class="text-center mb-2">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
            Koreksi Data Peserta
        </h1>
        <p class="text-zinc-500 text-sm mt-1">
            Review permintaan koreksi data peserta
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-950 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    @forelse ($pendingRequests as $request)
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-semibold text-zinc-900 dark:text-white">
                        {{ $request->person->nama }}
                    </h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $request->desa?->desa_asal ?? '-' }}
                        &middot;
                        {{ $request->event?->name ?? '-' }}
                    </p>
                    <p class="text-xs text-zinc-400 mt-1">
                        Diajukan: {{ $request->submitted_at?->format('d M Y H:i') }}
                    </p>
                </div>
                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900 dark:text-amber-300">
                    Pending
                </span>
            </div>

            <div class="mt-4 space-y-2">
                @if ($request->requested_name)
                    <div class="rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                        <p class="text-zinc-500 dark:text-zinc-400">Nama</p>
                        <p class="text-zinc-400 line-through">{{ $request->metadata['current_name'] ?? '-' }}</p>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $request->requested_name }}</p>
                    </div>
                @endif

                @if ($request->requested_birth_date)
                    <div class="rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                        <p class="text-zinc-500 dark:text-zinc-400">Tanggal Lahir</p>
                        <p class="text-zinc-400 line-through">{{ $request->metadata['current_birth_date'] ?? '-' }}</p>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $request->requested_birth_date->format('Y-m-d') }}</p>
                    </div>
                @endif

                @if ($request->reason)
                    <div class="rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                        <p class="text-zinc-500 dark:text-zinc-400">Alasan</p>
                        <p class="text-zinc-700 dark:text-zinc-300">{{ $request->reason }}</p>
                    </div>
                @endif
            </div>

            @if ($confirmingRejectId === $request->id)
                <div class="mt-4 space-y-2">
                    <textarea
                        wire:model="rejectReason"
                        placeholder="Alasan penolakan (opsional)"
                        class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                        rows="2"
                    ></textarea>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            wire:click="reject({{ $request->id }})"
                            wire:loading.attr="disabled"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                        >
                            Konfirmasi Tolak
                        </button>
                        <button
                            type="button"
                            wire:click="cancelReject"
                            class="rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                        >
                            Batal
                        </button>
                    </div>
                </div>
            @else
                <div class="mt-4 flex gap-2">
                    <button
                        type="button"
                        wire:click="approve({{ $request->id }})"
                        wire:loading.attr="disabled"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                    >
                        Setujui
                    </button>
                    <button
                        type="button"
                        wire:click="confirmReject({{ $request->id }})"
                        wire:loading.attr="disabled"
                        class="rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-800 dark:bg-zinc-900 dark:text-red-400 dark:hover:bg-red-950 disabled:opacity-50"
                    >
                        Tolak
                    </button>
                </div>
            @endif
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900/50">
            <p class="text-sm text-zinc-400">Tidak ada permintaan koreksi yang menunggu review.</p>
        </div>
    @endforelse
</div>
