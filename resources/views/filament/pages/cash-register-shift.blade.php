<x-filament-panels::page>
    <div class="max-w-lg mx-auto">
        @if($activeShift)
            {{-- ── Close Shift ── --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-semibold text-sm">Shift Aktif</h2>
                        <p class="text-xs text-gray-500">Dibuka {{ $activeShift->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>

                <div class="px-5 py-4 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
                            <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wider">Saldo Awal</div>
                            <div class="text-lg font-bold mt-1 tabular-nums">Rp{{ number_format($activeShift->opening_balance, 0, ',', '.') }}</div>
                        </div>
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-3">
                            <div class="text-[11px] font-medium text-emerald-600 uppercase tracking-wider">Penjualan</div>
                            <div class="text-lg font-bold mt-1 text-emerald-600 dark:text-emerald-400 tabular-nums">Rp{{ number_format($activeShift->totalSales(), 0, ',', '.') }}</div>
                        </div>
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                            <div class="text-[11px] font-medium text-blue-600 uppercase tracking-wider">Kas Masuk/Keluar</div>
                            <div class="text-lg font-bold mt-1 text-blue-600 dark:text-blue-400 tabular-nums">Rp{{ number_format($activeShift->totalCashInOut(), 0, ',', '.') }}</div>
                        </div>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3">
                            <div class="text-[11px] font-medium text-amber-600 uppercase tracking-wider">Saldo Seharusnya</div>
                            <div class="text-lg font-bold mt-1 tabular-nums">Rp{{ number_format($activeShift->expectedBalance(), 0, ',', '.') }}</div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Saldo Fisik Akhir</label>
                        <input type="number" wire:model="actualCash" placeholder="Masukkan saldo fisik..."
                               class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent px-3 py-2 text-sm
                                      focus:ring-2 focus:ring-gray-400 focus:border-gray-400 outline-none transition-shadow" />
                    </div>

                    <button wire:click="closeShift"
                            class="w-full rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-semibold text-sm px-4 py-2.5 transition-all active:scale-[0.99] shadow-sm">
                        Tutup Shift
                    </button>
                </div>
            </div>
        @else
            {{-- ── Open Shift ── --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-semibold text-sm">Buka Shift Baru</h2>
                        <p class="text-xs text-gray-500">Mulai sesi kasir Anda</p>
                    </div>
                </div>

                <div class="px-5 py-4 space-y-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Anda harus membuka shift kasir sebelum bisa melakukan transaksi.
                    </p>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Saldo Awal</label>
                        <input type="number" wire:model="openingBalance" placeholder="Masukkan saldo awal..."
                               class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent px-3 py-2 text-sm
                                      focus:ring-2 focus:ring-gray-400 focus:border-gray-400 outline-none transition-shadow" />
                    </div>

                    <button wire:click="openShift"
                            class="w-full rounded-lg bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-semibold text-sm px-4 py-2.5 transition-all active:scale-[0.99] shadow-sm hover:bg-gray-800 dark:hover:bg-gray-200">
                        Buka Shift
                    </button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
