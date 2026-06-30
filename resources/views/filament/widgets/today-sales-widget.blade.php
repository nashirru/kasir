<x-filament-widgets::widget>
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-6">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Penjualan Hari Ini</h3>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">Ringkasan Transaksi</p>
                </div>
            </div>
            <span class="text-xs text-gray-400 dark:text-gray-500">{{ now()->format('d M Y') }}</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            {{-- Card 1: Total Transaksi --}}
            <div class="relative p-5 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100/50 dark:from-blue-950/20 dark:to-blue-900/10 border border-blue-200/60 dark:border-blue-800/30">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wider">Total Transaksi</p>
                        <p class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $totalTransactions }}</p>
                    </div>
                    <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-blue-200/60 dark:bg-blue-800/40">
                        <svg class="w-4.5 h-4.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 text-xs text-blue-500 dark:text-blue-400">
                    Transaksi berhasil hari ini
                </div>
            </div>

            {{-- Card 2: Total Revenue --}}
            <div class="relative p-5 rounded-xl bg-gradient-to-br from-emerald-50 to-emerald-100/50 dark:from-emerald-950/20 dark:to-emerald-900/10 border border-emerald-200/60 dark:border-emerald-800/30">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Total Revenue</p>
                        <p class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
                    </div>
                    <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-emerald-200/60 dark:bg-emerald-800/40">
                        <svg class="w-4.5 h-4.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 text-xs text-emerald-500 dark:text-emerald-400">
                    Total pendapatan hari ini
                </div>
            </div>

            {{-- Card 3: Rata-rata --}}
            <div class="relative p-5 rounded-xl bg-gradient-to-br from-violet-50 to-violet-100/50 dark:from-violet-950/20 dark:to-violet-900/10 border border-violet-200/60 dark:border-violet-800/30">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium text-violet-600 dark:text-violet-400 uppercase tracking-wider">Rata-rata</p>
                        <p class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Rp {{ number_format($averagePerTransaction, 0, ',', '.') }}</p>
                    </div>
                    <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-violet-200/60 dark:bg-violet-800/40">
                        <svg class="w-4.5 h-4.5 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 text-xs text-violet-500 dark:text-violet-400">
                    Rata-rata per transaksi
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
