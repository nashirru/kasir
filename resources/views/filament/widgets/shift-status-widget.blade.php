<x-filament-widgets::widget>
    <div class="p-6 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $activeShift ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-gray-100 dark:bg-gray-700' }}">
                    @if($activeShift)
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @else
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    @endif
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Status Shift</h3>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $activeShift ? 'Shift Sedang Berjalan' : 'Tidak Ada Shift Aktif' }}
                    </p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium
                {{ $activeShift
                    ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300'
                    : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $activeShift ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400' }}"></span>
                {{ $activeShift ? 'Aktif' : 'Tutup' }}
            </span>
        </div>

        @if($activeShift)
            {{-- Shift details grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Saldo Awal</p>
                    <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($activeShift->opening_balance, 0, ',', '.') }}</p>
                </div>
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Expected Balance</p>
                    <p class="mt-1 text-xl font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($expectedBalance, 0, ',', '.') }}</p>
                </div>
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Transaksi</p>
                    <p class="mt-1 text-xl font-bold text-blue-600 dark:text-blue-400">{{ $totalTransactions }}</p>
                </div>
            </div>

            <div class="mt-4 text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Dibuka {{ $activeShift->opened_at ? $activeShift->opened_at->format('H:i') : '-' }} WIB
            </div>
        @else
            {{-- No shift message --}}
            <div class="text-center py-4">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Buka shift dulu untuk mulai bertransaksi</p>
                <a href="{{ \App\Filament\Pages\CashRegisterShift::getUrl() }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-500 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Buka Shift Baru
                </a>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
