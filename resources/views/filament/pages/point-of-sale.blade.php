<x-filament-panels::page>
    <div class="flex gap-4" x-data="{ search: '', barcode: '' }">
        {{-- Left: Product Grid --}}
        <div class="flex-1">
            <div class="mb-4 flex gap-2">
                <x-filament::input.wrapper class="flex-1">
                    <x-filament::input
                        type="text"
                        placeholder="Cari produk... (nama, SKU, barcode)"
                        wire:model.live.debounce="searchQuery"
                    />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper class="w-48">
                    <x-filament::input
                        type="text"
                        placeholder="Scan barcode..."
                        wire:model.live="barcodeInput"
                        wire:keydown.enter="scanBarcode"
                        x-ref="barcodeInput"
                        x-init="$watch('$wire.barcodeInput', () => { if($wire.barcodeInput) $wire.scanBarcode(); })"
                        autofocus
                    />
                </x-filament::input.wrapper>
            </div>

            <div class="grid grid-cols-4 gap-3">
                @foreach($this->products as $product)
                    <button wire:click="addToCart({{ $product->id }})"
                            class="p-3 border rounded-lg hover:bg-blue-50 text-left transition
                                   {{ $product->stok?->first()?->qty > 0 ? '' : 'opacity-50' }}">
                        <div class="font-semibold text-sm">{{ $product->nama }}</div>
                        <div class="text-blue-600 font-bold mt-1">
                            Rp {{ number_format($product->harga_jual, 0, ',', '.') }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            Stok: {{ $product->stok?->first()?->qty ?? 0 }} {{ $product->baseUnit?->nama ?? '' }}
                        </div>
                    </button>
                @endforeach
            </div>
            <div class="mt-4">
                {{ $this->products->links() }}
            </div>
        </div>

        {{-- Right: Cart --}}
        <div class="w-96 border-l pl-4">
            <h3 class="font-bold text-lg mb-3">Keranjang</h3>

            <div class="space-y-2 max-h-96 overflow-y-auto mb-4">
                @foreach($cart as $key => $item)
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                        <div class="flex-1">
                            <div class="font-medium text-sm">{{ $item['nama'] }}</div>
                            <div class="text-xs text-gray-500">Rp {{ number_format($item['price'], 0, ',', '.') }}</div>
                        </div>
                        <div class="flex items-center gap-1">
                            <button wire:click="updateQty({{ $item['product_id'] }}, {{ $item['qty'] - 1 }})"
                                    class="w-6 h-6 rounded bg-gray-200 text-center text-sm">-</button>
                            <span class="w-8 text-center">{{ $item['qty'] }}</span>
                            <button wire:click="updateQty({{ $item['product_id'] }}, {{ $item['qty'] + 1 }})"
                                    class="w-6 h-6 rounded bg-gray-200 text-center text-sm">+</button>
                        </div>
                        <div class="ml-2 font-medium text-sm w-20 text-right">
                            Rp {{ number_format($item['qty'] * $item['price'], 0, ',', '.') }}
                        </div>
                        <button wire:click="removeItem({{ $item['product_id'] }})" class="ml-1 text-red-500 text-sm">✕</button>
                    </div>
                @endforeach
            </div>

            @if(empty($cart))
                <p class="text-gray-400 text-center py-8">Keranjang kosong</p>
            @endif

            <div class="border-t pt-3 space-y-2">
                <div class="flex justify-between text-sm">
                    <span>Subtotal</span>
                    <span>Rp {{ number_format($this->cartSubtotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm">Diskon (%)</span>
                    <x-filament::input type="number" wire:model.live="discount" class="w-24 text-right" min="0" max="100" step="1" />
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm">Pajak (%)</span>
                    <x-filament::input type="number" wire:model.live="taxPercent" class="w-24 text-right" min="0" max="100" step="1" />
                </div>
                <div class="flex justify-between font-bold text-lg pt-2 border-t">
                    <span>Total</span>
                    <span class="text-blue-600">Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="mt-4">
                <livewire:pos-checkout-modal />
            </div>
        </div>
    </div>

    {{-- Receipt Modal --}}
    @if(session('receipt'))
        @php $sale = session('receipt'); @endphp
        <x-filament::modal id="receipt-modal" :open="true">
            <div class="p-4" style="font-family: monospace; font-size: 12px; width: 58mm; margin: 0 auto;">
                <div class="text-center mb-2">
                    <strong>{{ $sale->outlet->nama }}</strong><br>
                    {{ $sale->outlet->alamat }}<br>
                    {{ $sale->outlet->telepon }}
                </div>
                <div class="border-t border-b py-1 mb-2 text-center">
                    STRUK PEMBELIAN
                </div>
                <div>No: {{ $sale->invoice_number }}</div>
                <div>{{ $sale->created_at->format('d/m/Y H:i') }}</div>
                <div class="border-t my-2"></div>
                @foreach($sale->items as $item)
                    <div class="flex justify-between">
                        <span>{{ $item->product->nama }} x{{ $item->qty }}</span>
                        <span>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                    </div>
                @endforeach
                <div class="border-t my-2"></div>
                <div class="flex justify-between font-bold">
                    <span>TOTAL</span>
                    <span>Rp {{ number_format($sale->total, 0, ',', '.') }}</span>
                </div>
                <div class="text-center mt-4 text-xs">Terima kasih telah berbelanja</div>
            </div>
            <x-slot name="footer">
                <x-filament::button onclick="window.print()">Print Struk</x-filament::button>
            </x-slot>
        </x-filament::modal>
    @endif
</x-filament-panels::page>
