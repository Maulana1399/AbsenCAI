<div class="space-y-6"
     x-data="{
         rawToken: null,
         showTokenModal: false,
         copied: false,
         viewToken: null,
         showViewModal: false,
         viewFallback: false,
         viewCopied: false,
         copyToken() {
             navigator.clipboard.writeText(this.rawToken)
                 .then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000); })
                 .catch(() => {})
         },
         closeModal() {
             this.showTokenModal = false;
             this.rawToken = null;
             this.copied = false;
         },
         viewCopyToken() {
             navigator.clipboard.writeText(this.viewToken)
                 .then(() => { this.viewCopied = true; setTimeout(() => this.viewCopied = false, 2000); })
                 .catch(() => {})
         },
         closeViewModal() {
             this.showViewModal = false;
             this.viewToken = null;
             this.viewFallback = false;
             this.viewCopied = false;
         }
     }"
     x-on:pengajian-raw-token-created.window="rawToken = $event.detail.token; showTokenModal = true; copied = false"
     x-on:pengajian-view-token.window="viewToken = $event.detail.token; showViewModal = true; viewFallback = false; viewCopied = false"
     x-on:pengajian-view-token-fallback.window="viewToken = null; showViewModal = true; viewFallback = true; viewCopied = false">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Akses Desa</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Kelola grant akses untuk desa.</p>
        </div>
        <flux:button wire:click="toggleCreateForm" variant="primary">
            {{ $showCreateForm ? 'Batal' : 'Tambah Grant' }}
        </flux:button>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    @if ($showCreateForm)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-950">
            <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Grant Baru</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                @if ($activeEvent)
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Event</label>
                    <div class="block w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                        {{ $activeEvent->name }}
                    </div>
                </div>
                @endif

                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Desa</label>
                    <select wire:model="desaId" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-500 focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <option value="">-- Pilih Desa --</option>
                        @foreach ($desas as $desa)
                            <option value="{{ $desa->id }}">{{ $desa->desa_asal }}</option>
                        @endforeach
                    </select>
                    @error('desaId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Berlaku Dari</label>
                    <flux:input wire:model="validFrom" type="datetime-local" />
                    @error('validFrom') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Berlaku Sampai</label>
                    <flux:input wire:model="validUntil" type="datetime-local" />
                    @error('validUntil') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <flux:button wire:click="create" variant="primary" :loading="$processing">
                    Buat Grant
                </flux:button>
            </div>
        </div>
    @endif

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Event</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Desa</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Token Prefix</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Berlaku</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Dibuat Oleh</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($grants as $grant)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white">{{ $grant['event_name'] }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $grant['desa_name'] }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm"><code class="text-xs text-zinc-500 dark:text-zinc-400">{{ $grant['token_prefix'] }}...</code></td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            @php
                                $badge = match ($grant['status']) {
                                    'active' => ['bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200', 'Active'],
                                    'expired' => ['bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200', 'Expired'],
                                    'revoked' => ['bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200', 'Revoked'],
                                    default => ['bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200', 'Scheduled'],
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge[0] }}">{{ $badge[1] }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $grant['valid_from']?->format('d/m/Y H:i') ?? '-' }}<br>
                            <span class="text-xs dark:text-zinc-400">s.d.</span><br>
                            {{ $grant['valid_until']?->format('d/m/Y H:i') ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $grant['created_by'] ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            <div class="flex items-center gap-1">
                                <flux:button wire:click="viewToken({{ $grant['id'] }})" size="sm" variant="ghost" icon-trailing="eye">Lihat</flux:button>
                                @if ($grant['status'] === 'active' || $grant['status'] === 'scheduled')
                                    <flux:button wire:click="revoke({{ $grant['id'] }})" size="sm" variant="danger" icon-trailing="x-mark">Cabut</flux:button>
                                @elseif ($grant['status'] === 'revoked')
                                    <flux:button wire:click="confirmDelete({{ $grant['id'] }})" size="sm" variant="ghost" icon-trailing="trash">Hapus</flux:button>
                                @else
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500">-</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">Belum ada grant akses.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- One-Time Raw Token Reveal Modal --}}
    <div x-show="showTokenModal && rawToken" style="display: none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
         x-on:keydown.escape.window="closeModal()"
         x-on:click.self="closeModal()">
        <div class="w-full max-w-sm rounded-xl border border-zinc-200 bg-white p-5 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400">Salin Token Ini</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Token hanya ditampilkan sekali. Salin dan simpan di tempat aman sebelum menutup dialog.</p>

            <div class="mt-3">
                <textarea readonly
                          x-model="rawToken"
                          class="block w-full max-w-full resize-none rounded-lg border border-zinc-300 bg-zinc-50 p-3 text-sm font-mono leading-6 text-zinc-800 outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 select-all"
                          rows="3"
                          x-on:click="$event.target.select()"></textarea>
            </div>

            <div class="mt-4 flex flex-col gap-2">
                <button type="button"
                        x-on:click="copyToken()"
                        class="w-full inline-flex items-center justify-center rounded-lg bg-zinc-800 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                    <span x-show="!copied">Salin Token</span>
                    <span x-show="copied" style="display: none">Tersalin</span>
                </button>
                <button type="button"
                        x-on:click="closeModal()"
                        class="w-full inline-flex items-center justify-center rounded-lg border border-zinc-300 bg-white px-5 py-3 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">Tutup</button>
            </div>
        </div>
    </div>

    {{-- View Existing Token Modal --}}
    <div x-show="showViewModal" style="display: none"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
         x-on:keydown.escape.window="closeViewModal()"
         x-on:click.self="closeViewModal()">
        <div class="w-full max-w-lg rounded-xl border border-zinc-200 bg-white p-5 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-semibold uppercase tracking-wide text-zinc-700 dark:text-zinc-300">Access Token</p>

            <template x-if="viewToken">
                <div class="mt-3">
                    <textarea readonly
                              x-model="viewToken"
                              class="block w-full max-w-full resize-none rounded-lg border border-zinc-300 bg-zinc-50 p-3 text-sm font-mono leading-6 text-zinc-800 outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 select-all"
                              rows="3"
                              x-on:click="$event.target.select()"></textarea>
                </div>
            </template>

            <template x-if="viewFallback">
                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
                    Token ini dibuat sebelum fitur View Token tersedia.<br>
                    Silakan generate token baru apabila ingin dapat melihat kembali token di masa mendatang.
                </div>
            </template>

            <div class="mt-4 flex flex-col gap-2">
                <template x-if="viewToken">
                    <button type="button"
                            x-on:click="viewCopyToken()"
                            class="w-full inline-flex items-center justify-center rounded-lg bg-zinc-800 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                        <span x-show="!viewCopied">Copy</span>
                        <span x-show="viewCopied" style="display: none">Tersalin</span>
                    </button>
                </template>
                <button type="button"
                        x-on:click="closeViewModal()"
                        class="w-full inline-flex items-center justify-center rounded-lg border border-zinc-300 bg-white px-5 py-3 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">Close</button>
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div wire:key="delete-modal">
        @if ($deleteGrantId)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                 x-on:keydown.escape.window="$wire.cancelDelete()"
                 x-on:click.self="$wire.cancelDelete()">
                <div class="w-full max-w-sm rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-base font-bold text-red-700 dark:text-red-400">Hapus Grant?</p>
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Grant yang sudah dihapus tidak dapat dikembalikan. Lanjutkan?</p>

                    <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-end">
                        <button type="button"
                                wire:click="cancelDelete"
                                class="w-full sm:w-auto inline-flex items-center justify-center rounded-lg border border-zinc-300 bg-white px-5 py-3 text-sm font-semibold text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:hover:bg-zinc-700">Batal</button>
                        <flux:button wire:click="delete" variant="danger" class="w-full sm:w-auto">Ya, Hapus</flux:button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
