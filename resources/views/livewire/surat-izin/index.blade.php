<div class="space-y-6">
    {{-- Flash messages --}}
    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950/30 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filter + action bar --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:gap-3">
            <div class="flex-1">
                <input wire:model.live.debounce.300ms="search" type="text"
                    class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                    placeholder="{{ __('Nama peserta / nomor surat...') }}">
            </div>

            <div class="shrink-0">
                @php $statuses = ['' => 'Semua', 'draft' => 'Draft', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']; @endphp
                <div class="inline-flex items-center gap-1 rounded-xl bg-zinc-100 p-1 dark:bg-zinc-800">
                    @foreach($statuses as $val => $label)
                        <button wire:click="$set('filterStatus', '{{ $val }}')"
                            class="rounded-lg px-3 py-2 text-xs font-medium whitespace-nowrap transition {{ $filterStatus === $val ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-white' : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="flex-shrink-0">
                <livewire:surat-izin.create />
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-zinc-700 dark:text-zinc-300">
                <thead class="text-xs uppercase bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700 w-12">{{ __('No') }}</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">{{ __('Peserta') }}</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">{{ __('Jenis') }}</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">{{ __('Periode') }}</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">{{ __('Alasan') }}</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">{{ __('Status') }}</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">{{ __('Nomor Surat') }}</th>
                        <th class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suratList as $surat)
                        <tr class="bg-white dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $surat->peserta->nama ?? '-' }}</div>
                                <div class="text-xs text-zinc-500">{{ $surat->peserta->nip ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs font-medium">
                                @if($surat->jenis_izin === 'pulang')
                                    <span class="text-amber-600 dark:text-amber-400">{{ __('Pulang') }}</span>
                                @else
                                    <span class="text-purple-600 dark:text-purple-400">{{ __('Keluar') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ $surat->tanggal_mulai->format('d/m/Y') }} — {{ $surat->tanggal_selesai->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 max-w-xs truncate">{{ $surat->alasan }}</td>
                            <td class="px-4 py-3 whitespace-nowrap space-y-1">
                                @if ($surat->status === 'draft')
                                    <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ __('Draft') }}</span>
                                @elseif($surat->status === 'pending')
                                    <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">{{ __('Pending') }}</span>
                                @elseif($surat->status === 'approved')
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-300">{{ __('Approved') }}</span>
                                    @if($surat->isReturned())
                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">{{ __('Sudah Kembali') }}</span>
                                        <div class="text-[10px] text-zinc-400 mt-0.5">{{ $surat->returned_at->format('d/m/Y') }}</div>
                                    @endif
                                @elseif($surat->status === 'rejected')
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/30 dark:text-red-300">{{ __('Rejected') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs font-mono text-zinc-600 dark:text-zinc-400">
                                {{ $surat->nomor_surat ?? '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex flex-wrap gap-1.5">
                                    @if ($surat->isDraft())
                                        <button wire:click="submit({{ $surat->id }})" wire:confirm="{{ __('Submit surat izin ini?') }}"
                                            class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900"
                                            style="background-color: #2563eb;">
                                            {{ __('Submit') }}
                                        </button>
                                    @endif

                                    @if ($surat->isPending())
                                        <button wire:click="confirmApprove({{ $surat->id }})"
                                            class="inline-flex items-center rounded-xl bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900"
                                            style="background-color: #16a34a;">
                                            {{ __('Setujui') }}
                                        </button>
                                        <button wire:click="reject({{ $surat->id }})" wire:confirm="{{ __('Tolak surat izin ini?') }}"
                                            class="inline-flex items-center rounded-xl bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900"
                                            style="background-color: #dc2626;">
                                            {{ __('Tolak') }}
                                        </button>
                                    @endif

                                    @if ($surat->isApproved() && !$surat->isReturned())
                                        <button wire:click="confirmReturn({{ $surat->id }})"
                                            class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900"
                                            style="background-color: #2563eb;">
                                            {{ __('Tandai Kembali') }}
                                        </button>
                                    @endif
                                    @if ($surat->isApproved())
                                        <a href="{{ route('surat-izin.print', $surat->id) }}" target="_blank"
                                            class="inline-flex items-center rounded-xl border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                                            {{ __('Print') }}
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-zinc-500">
                                <div class="rounded-xl border border-dashed border-zinc-200 p-6 dark:border-zinc-700">
                                    {{ __('Belum ada surat izin.') }}
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Approve confirmation + result modal --}}
    <flux:modal name="approve-surat-izin" class="md:w-[28rem]">
        <div class="space-y-6">
            @if ($approveResult === null && $approveSuratId)
                @php $s = \App\Models\SuratIzin::with('peserta')->find($approveSuratId); @endphp
                @if ($s)
                    <div>
                        <flux:heading size="lg">{{ __('Setujui Surat Izin') }}</flux:heading>
                    </div>
                    <div class="space-y-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <p><span class="font-medium">{{ __('Peserta:') }}</span> {{ $s->peserta->nama ?? '-' }}</p>
                        <p><span class="font-medium">{{ __('Alasan:') }}</span> {{ $s->alasan }}</p>
                        <p><span class="font-medium">{{ __('Periode:') }}</span> {{ $s->tanggal_mulai->format('d/m/Y') }} — {{ $s->tanggal_selesai->format('d/m/Y') }}</p>
                    </div>
                    <div class="flex gap-2">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                        </flux:modal.close>
                        <flux:button wire:click="approve" wire:loading.attr="disabled" variant="primary">
                            {{ __('Ya, Setujui') }}
                        </flux:button>
                    </div>
                @endif

            @elseif ($approveResult && !isset($approveResult['error']))
                <div>
                    <flux:heading size="lg">{{ __('Surat Izin Disetujui') }}</flux:heading>
                </div>
                <div class="space-y-3 text-sm">
                    <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800 dark:border-green-800 dark:bg-green-950/30 dark:text-green-300">
                        ✅ {{ __('Surat Izin') }} <strong>{{ $approveResult['surat']->nomor_surat }}</strong> {{ __('berhasil disetujui.') }}
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 text-center dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="text-lg font-bold text-green-700 dark:text-green-400">{{ count($approveResult['created']) }}</div>
                            <div class="text-xs text-zinc-500">{{ __('Izin Dibuat') }}</div>
                        </div>
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 text-center dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="text-lg font-bold text-yellow-700 dark:text-yellow-400">{{ count($approveResult['skipped_hadir']) }}</div>
                            <div class="text-xs text-zinc-500">{{ __('Skip Hadir') }}</div>
                        </div>
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 text-center dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="text-lg font-bold text-blue-700 dark:text-blue-400">{{ count($approveResult['skipped_izin']) }}</div>
                            <div class="text-xs text-zinc-500">{{ __('Skip Izin') }}</div>
                        </div>
                    </div>
                    @if($approveResult['sesi_found'] === 0)
                        <p class="text-xs text-zinc-500 italic">{{ __('Tidak ada sesi absensi dalam rentang tanggal ini.') }}</p>
                    @endif
                </div>
                <div class="flex">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="primary" wire:click="closeApproveModal">{{ __('Tutup') }}</flux:button>
                    </flux:modal.close>
                </div>

            @elseif ($approveResult && isset($approveResult['error']))
                <div>
                    <flux:heading size="lg">{{ __('Gagal') }}</flux:heading>
                </div>
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-300">
                    {{ $approveResult['error'] }}
                </div>
                <div class="flex">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Tutup') }}</flux:button>
                    </flux:modal.close>
                </div>
            @endif
        </div>
    </flux:modal>

    {{-- Tandai Kembali modal --}}
    <flux:modal name="return-surat-izin" class="md:w-[24rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Tandai Kembali') }}</flux:heading>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Tanggal Kembali') }}</label>
                    <input type="date" wire:model="returnDate"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    @error('returnDate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <p class="text-xs text-zinc-500">{{ __('Peserta dianggap sudah kembali sejak tanggal ini. Izin pada sesi setelah tanggal ini akan dihapus.') }}</p>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" wire:click="closeReturnModal">{{ __('Batal') }}</flux:button>
                </flux:modal.close>
                <flux:button wire:click="markReturned" wire:loading.attr="disabled" variant="primary">
                    {{ __('Ya, Tandai Kembali') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
