<x-filament-panels::page>
    <div class="max-w-xl mx-auto">
        @if($activeShift)
            {{-- Close Shift --}}
            <x-filament::section>
                <x-slot name="heading">
                    Shift Aktif — {{ $activeShift->created_at->format('d/m/Y H:i') }}
                </x-slot>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-3 bg-gray-50 rounded">
                            <div class="text-sm text-gray-500">Saldo Awal</div>
                            <div class="text-xl font-bold">Rp {{ number_format($activeShift->opening_balance, 0, ',', '.') }}</div>
                        </div>
                        <div class="p-3 bg-green-50 rounded">
                            <div class="text-sm text-gray-500">Penjualan</div>
                            <div class="text-xl font-bold text-green-600">Rp {{ number_format($activeShift->totalSales(), 0, ',', '.') }}</div>
                        </div>
                        <div class="p-3 bg-blue-50 rounded">
                            <div class="text-sm text-gray-500">Kas Masuk/Keluar</div>
                            <div class="text-xl font-bold text-blue-600">Rp {{ number_format($activeShift->totalCashInOut(), 0, ',', '.') }}</div>
                        </div>
                        <div class="p-3 bg-yellow-50 rounded">
                            <div class="text-sm text-gray-500">Saldo Seharusnya</div>
                            <div class="text-xl font-bold">Rp {{ number_format($activeShift->expectedBalance(), 0, ',', '.') }}</div>
                        </div>
                    </div>

                    <x-filament::input.wrapper label="Saldo Fisik Akhir">
                        <x-filament::input
                            type="number"
                            wire:model="actualCash"
                            placeholder="Masukkan saldo fisik..."
                        />
                    </x-filament::input.wrapper>

                    <x-filament::button color="warning" wire:click="closeShift">
                        Tutup Shift
                    </x-filament::button>
                </div>
            </x-filament::section>
        @else
            {{-- Open Shift --}}
            <x-filament::section>
                <x-slot name="heading">
                    Buka Shift Baru
                </x-slot>

                <div class="space-y-4">
                    <p class="text-sm text-gray-500">
                        Anda harus membuka shift kasir sebelum bisa melakukan transaksi.
                    </p>

                    <x-filament::input.wrapper label="Saldo Awal">
                        <x-filament::input
                            type="number"
                            wire:model="openingBalance"
                            placeholder="Masukkan saldo awal..."
                        />
                    </x-filament::input.wrapper>

                    <x-filament::button color="success" wire:click="openShift">
                        Buka Shift
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
