<div>
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-3 mb-4">
        <flux:input wire:model.live="search" placeholder="Cari nama..." class="w-full sm:max-w-xs" />

        <select wire:model.live="filterStatus"
                class="block w-full sm:w-40 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 shadow-sm focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
            <option value="">Semua Status</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
        </select>

        <select wire:model.live="filterDesaId"
                class="block w-full sm:w-40 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 shadow-sm focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
            <option value="">Semua Desa</option>
            @foreach ($desas as $desa)
                <option value="{{ $desa->id }}">{{ $desa->desa_asal }}</option>
            @endforeach
        </select>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-zinc-700 dark:text-zinc-300">
                <thead class="text-xs uppercase bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Status</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Nama Person</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Desa</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Kelompok</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Field</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Tanggal Pengajuan</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Pengaju</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($requests as $request)
                    <tr class="bg-white dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                        <td class="px-4 py-3">
                            @php
                                $badge = match ($request->status) {
                                    'pending' => ['bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200', 'Pending'],
                                    'approved' => ['bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200', 'Approved'],
                                    'rejected' => ['bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200', 'Rejected'],
                                    default => ['bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200', $request->status],
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge[0] }}">{{ $badge[1] }}</span>
                        </td>
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $request->person?->nama ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $request->desa?->desa_asal ?? ($request->person?->desa?->desa_asal ?? '-') }}</td>
                        <td class="px-4 py-3">{{ $request->person?->kelompok?->kelompok_asal ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if ($request->requested_birth_date)
                                <span class="text-xs text-zinc-500">Tanggal Lahir</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-500">{{ $request->submitted_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td class="px-4 py-3 text-zinc-500">{{ $request->person?->nama ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <flux:button wire:click="review({{ $request->id }})" size="sm">Review</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            Tidak ada permintaan perubahan.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $requests->links() }}
    </div>

    {{-- Review Modal --}}
    @if ($reviewingId)
        @php $reviewRequest = \App\Models\IdentityCorrectionRequest::with(['person.desa', 'person.kelompok'])->find($reviewingId); @endphp
        @if ($reviewRequest)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                 x-on:keydown.escape.window="$wire.closeReview()"
                 x-on:click.self="$wire.closeReview()">
                <div class="w-full max-w-lg rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-base font-bold text-zinc-900 dark:text-white">Review Permintaan Perubahan</p>

                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Nama Person</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $reviewRequest->person?->nama ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Desa</span>
                            <span class="text-zinc-900 dark:text-white">{{ $reviewRequest->person?->desa?->desa_asal ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Kelompok</span>
                            <span class="text-zinc-900 dark:text-white">{{ $reviewRequest->person?->kelompok?->kelompok_asal ?? '-' }}</span>
                        </div>
                    </div>

                    <flux:separator class="my-4" />

                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 mb-2">Data Lama</p>
                    <div class="rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                        <p><span class="text-zinc-500">Tanggal Lahir:</span> {{ $reviewRequest->metadata['current_birth_date'] ?? '-' }}</p>
                    </div>

                    <flux:separator class="my-4" />

                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 mb-2">Data Pengajuan</p>
                    <div class="rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800 space-y-1">
                        <p><span class="text-zinc-500">Tanggal Lahir Baru:</span> {{ $reviewRequest->requested_birth_date?->format('Y-m-d') ?? '-' }}</p>
                        @if ($reviewRequest->reason)
                            <p><span class="text-zinc-500">Alasan:</span> {{ $reviewRequest->reason }}</p>
                        @endif
                        <p><span class="text-zinc-500">Tanggal Pengajuan:</span> {{ $reviewRequest->submitted_at?->format('d/m/Y H:i') ?? '-' }}</p>
                        <p><span class="text-zinc-500">Pengaju:</span> {{ $reviewRequest->person?->nama ?? '-' }}</p>
                    </div>

                    <div class="mt-4">
                        <textarea wire:model="operatorNotes"
                                  placeholder="Catatan operator{{ $reviewRequest->status === 'pending' ? ' (wajib diisi jika menolak)' : '' }}"
                                  rows="2"
                                  class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                        ></textarea>
                    </div>

                    @if ($reviewRequest->status === 'pending')
                        <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
                            <flux:button wire:click="approve" :loading="$processing" variant="primary">Setujui</flux:button>
                            <flux:button wire:click="reject" :loading="$processing" variant="danger">Tolak</flux:button>
                            <flux:button wire:click="closeReview" variant="ghost">Tutup</flux:button>
                        </div>
                    @else
                        <div class="mt-4 flex justify-end">
                            <flux:button wire:click="closeReview" variant="ghost">Tutup</flux:button>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif

    <script>
        document.addEventListener('correction-validation-error', function (e) {
            alert(e.detail.message);
        })
    </script>
</div>
