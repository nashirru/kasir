<x-filament-panels::page>
    {{-- Scanner Bar (floating top)  --}}
    <div class="mb-4"
         x-data="{
             flash: '',
             init() {
                 this.$watch('flash', val => { if (val) setTimeout(() => this.flash = '', 400); });
                 Livewire.on('scan-feedback', ({ status }) => { this.flash = status; });
                 Livewire.on('cart-cleared', () => {});
             },
             focusScanner() { this.$nextTick(() => { this.$refs.scanner?.focus(); }); }
         }"
         x-init="setInterval(() => { $refs.scanner?.focus(); }, 3000);">

        {{-- Scanner bar --}}
        <div class="flex items-center gap-3 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm px-4 py-3"
             :class="{ 'ring-2 ring-emerald-400 border-emerald-300': flash === 'success', 'ring-2 ring-red-400 border-red-300': flash === 'error' }">

            {{-- Scanner icon + indicator --}}
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 min-w-[140px]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
                <span class="hidden sm:inline font-medium">
                    <span x-show="flash !== 'error'">Scanner Siap</span>
                    <span x-show="flash === 'error'" class="text-red-500">Tidak ditemukan</span>
                </span>
                <span class="inline sm:hidden">Scan</span>
            </div>

            {{-- Scan input --}}
            <input type="text" x-ref="scanner"
                   wire:model.live="barcodeInput" wire:keydown.enter="scanBarcode"
                   placeholder="Scan atau ketik barcode... (ENTER)"
                   class="flex-1 bg-transparent border-0 outline-none text-sm focus:ring-0 px-2 py-1
                          placeholder:text-gray-400 dark:placeholder:text-gray-500"
                   autofocus />

            {{-- Search --}}
            <div class="hidden sm:flex items-center gap-1 border-l border-gray-200 dark:border-gray-700 pl-3 ml-1">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" wire:model.live.debounce="searchQuery"
                       placeholder="Cari produk..."
                       class="bg-transparent border-0 outline-none text-sm focus:ring-0 py-1 w-36 lg:w-48
                              placeholder:text-gray-400 dark:placeholder:text-gray-500" />
            </div>

            {{-- Cart count badge --}}
            <div class="flex items-center gap-1.5 bg-gray-100 dark:bg-gray-800 rounded-lg px-3 py-1.5 text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                </svg>
                <span>{{ $this->cartCount }}</span>
            </div>
        </div>
    </div>

    {{-- Category Pills  --}}
    @if($this->categories->isNotEmpty())
    <div class="mb-4 flex items-center gap-2 overflow-x-auto pb-2">
        <button wire:click="$set('selectedCategory', null)"
                class="whitespace-nowrap px-3.5 py-1.5 rounded-full text-xs font-medium transition-all
                       {{ is_null($selectedCategory) ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
            Semua
        </button>
        @foreach($this->categories as $cat)
            <button wire:click="$set('selectedCategory', {{ $cat->id }})"
                    class="whitespace-nowrap px-3.5 py-1.5 rounded-full text-xs font-medium transition-all
                           {{ $selectedCategory === $cat->id ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                {{ $cat->nama }}
            </button>
        @endforeach
    </div>
    @endif

    {{-- Main Layout: Products + Cart  --}}
    <div class="flex flex-col lg:flex-row gap-4 lg:max-h-[calc(100vh-200px)] overflow-hidden">

        {{--──── LEFT: Product Grid ──── --}}
        <div class="flex-1 min-w-0">
            @if($this->products->isEmpty())
                <div class="text-center py-10 text-gray-400 dark:text-gray-600">
                    <svg class="w-8 h-8 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <p class="text-sm">Tidak ada produk ditemukan</p>
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
                    @foreach($this->products as $product)
                        @php $stok = $product->stok?->first()?->qty ?? 0; @endphp
                        <button wire:click="addToCart({{ $product->id }})"
                                @click="$refs.scanner?.focus()"
                                class="group relative bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-3 text-left transition-all
                                       hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 hover:-translate-y-0.5 active:translate-y-0
                                       {{ $stok <= 0 ? 'opacity-40 pointer-events-none' : '' }}">

                            {{-- Stock badge --}}
                            <div class="absolute top-2 right-2">
                                @if($stok <= 0)
                                    <span class="text-[10px] font-semibold text-red-500 bg-red-50 dark:bg-red-950 px-1.5 py-0.5 rounded">HABIS</span>
                                @elseif($stok <= 5)
                                    <span class="text-[10px] font-semibold text-amber-600 bg-amber-50 dark:bg-amber-950 px-1.5 py-0.5 rounded">{{ $stok }} {{ $product->baseUnit?->nama }}</span>
                                @endif
                            </div>

                            {{-- Product icon placeholder --}}
                            <div class="w-full aspect-square rounded-lg bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 mb-2 flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-300 dark:text-gray-600 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>

                            {{-- Product info --}}
                            <div class="text-sm font-medium leading-tight line-clamp-2 mb-1">{{ $product->nama }}</div>
                            <div class="text-sm font-bold text-emerald-600 dark:text-emerald-400">
                                Rp{{ number_format($product->harga_jual, 0, ',', '.') }}
                            </div>
                            <div class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                                @if($stok > 0) Stok: {{ $stok }} @endif
                            </div>
                        </button>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-4 text-sm">
                    {{ $this->products->links() }}
                </div>
            @endif
        </div>

        {{-- RIGHT: Cart Panel --}}
        <div class="w-full lg:w-80 shrink-0 overflow-y-auto"
             x-data="{ open: true }">

            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden sticky top-4">

                {{-- Header --}}
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="font-semibold text-sm">Keranjang</h3>
                    <div class="flex items-center gap-2">
                        @if(!empty($cart))
                            <span class="text-xs text-gray-500">{{ $this->cartCount }} item</span>
                            <button wire:click="clearCart" class="text-xs text-red-500 hover:text-red-700 transition-colors">Hapus semua</button>
                        @endif
                        <button @click="open = !open" class="lg:hidden text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Cart Items --}}
                <div x-show="open" class="divide-y divide-gray-100 dark:divide-gray-800 max-h-[50vh] overflow-y-auto">
                    @forelse($cart as $key => $item)
                        <div class="px-4 py-3 flex items-center gap-3 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group">
                            {{-- Item details --}}
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium truncate">{{ $item['nama'] }}</div>
                                <div class="text-xs text-gray-500">Rp{{ number_format($item['price'], 0, ',', '.') }}</div>
                            </div>

                            {{-- Qty controls --}}
                            <div class="flex items-center gap-1">
                                <button wire:click="updateQty({{ $item['product_id'] }}, {{ $item['qty'] - 1 }})"
                                        @click="$refs.scanner?.focus()"
                                        class="w-7 h-7 rounded-md border border-gray-200 dark:border-gray-700 flex items-center justify-center text-sm
                                               hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors text-gray-600 dark:text-gray-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                                </button>
                                <span class="w-8 text-center text-sm font-medium tabular-nums">{{ $item['qty'] }}</span>
                                <button wire:click="updateQty({{ $item['product_id'] }}, {{ $item['qty'] + 1 }})"
                                        @click="$refs.scanner?.focus()"
                                        class="w-7 h-7 rounded-md border border-gray-200 dark:border-gray-700 flex items-center justify-center text-sm
                                               hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors text-gray-600 dark:text-gray-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                </button>
                            </div>

                            {{-- Subtotal --}}
                            <div class="text-sm font-medium tabular-nums w-20 text-right">
                                Rp{{ number_format($item['qty'] * $item['price'], 0, ',', '.') }}
                            </div>

                            {{-- Remove --}}
                            <button wire:click="removeItem({{ $item['product_id'] }})"
                                    @click="$refs.scanner?.focus()"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity text-gray-400 hover:text-red-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    @empty
                        <div class="py-8 text-center text-gray-400 dark:text-gray-600">
                            <svg class="w-8 h-8 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                            </svg>
                            <p class="text-sm">Keranjang kosong</p>
                            <p class="text-xs mt-1">Scan atau klik produk untuk mulai</p>
                        </div>
                    @endforelse
                </div>

                {{-- Summary + Checkout --}}
                @if(!empty($cart))
                <div x-show="open" class="border-t border-gray-100 dark:border-gray-800 px-4 py-3 space-y-2.5">
                    {{-- Subtotal --}}
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="tabular-nums">Rp{{ number_format($this->cartSubtotal, 0, ',', '.') }}</span>
                    </div>

                    {{-- Discount --}}
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm text-gray-500">Diskon</span>
                        <div class="flex items-center gap-1.5">
                            <input type="number" wire:model.live="discount"
                                   class="w-16 text-right text-sm border border-gray-200 dark:border-gray-700 rounded-md px-2 py-1
                                          bg-transparent focus:ring-1 focus:ring-gray-400 focus:border-gray-400 outline-none tabular-nums"
                                   min="0" max="100" step="1" />
                            <span class="text-xs text-gray-400">%</span>
                        </div>
                    </div>
                    @if($discount > 0)
                        <div class="flex justify-between text-xs text-red-500">
                            <span></span>
                            <span class="tabular-nums">- Rp{{ number_format($this->discountAmount, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    {{-- Tax --}}
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm text-gray-500">Pajak</span>
                        <div class="flex items-center gap-1.5">
                            <input type="number" wire:model.live="taxPercent"
                                   class="w-16 text-right text-sm border border-gray-200 dark:border-gray-700 rounded-md px-2 py-1
                                          bg-transparent focus:ring-1 focus:ring-gray-400 focus:border-gray-400 outline-none tabular-nums"
                                   min="0" max="100" step="0.5" />
                            <span class="text-xs text-gray-400">%</span>
                        </div>
                    </div>

                    {{-- Payment Method --}}
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm text-gray-500">Pembayaran</span>
                        <select wire:model.live="paymentMethod"
                                class="text-sm border border-gray-200 dark:border-gray-700 rounded-md px-2 py-1
                                       bg-transparent focus:ring-1 focus:ring-gray-400 focus:border-gray-400 outline-none">
                            <option value="tunai">Tunai</option>
                            <option value="qris">QRIS</option>
                            <option value="transfer">Transfer</option>
                            <option value="kartu">Kartu</option>
                        </select>
                    </div>

                    {{-- Total --}}
                    <div class="flex justify-between font-bold text-base pt-2 border-t border-gray-200 dark:border-gray-700">
                        <span>Total</span>
                        <span class="text-emerald-600 dark:text-emerald-400 tabular-nums">Rp{{ number_format($this->total, 0, ',', '.') }}</span>
                    </div>

                    {{-- Checkout Button --}}
                    <button wire:click="checkout"
                            @click="$refs.scanner?.focus()"
                            class="w-full mt-2 bg-gray-900 dark:bg-white hover:bg-gray-800 dark:hover:bg-gray-200
                                   text-white dark:text-gray-900 font-semibold text-sm rounded-lg px-4 py-3
                                   transition-all active:scale-[0.99] shadow-sm">
                        Bayar Rp{{ number_format($this->total, 0, ',', '.') }}
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Receipt Modal (thermal print style)  --}}
    @if(session('receipt'))
        @php $sale = session('receipt'); @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
             x-data x-init="$nextTick(() => { setTimeout(() => window.print(), 300); })">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-sm w-full mx-4 overflow-hidden">
                {{-- Receipt content --}}
                <div class="p-6 text-center" style="font-family: 'Courier New', monospace; font-size: 12px;">
                    <div class="text-sm font-bold mb-1">{{ $sale->outlet->nama }}</div>
                    <div class="text-[10px] text-gray-500">{{ $sale->outlet->alamat }}</div>
                    <div class="text-[10px] text-gray-500 mb-3">{{ $sale->outlet->telepon }}</div>

                    <div class="border-t border-b border-dashed border-gray-300 py-1 mb-2 text-[11px] font-bold tracking-widest">
                        STRUK PEMBELIAN
                    </div>

                    <div class="text-left text-[10px] mb-2">
                        <div>No: {{ $sale->invoice_number }}</div>
                        <div>{{ $sale->created_at->format('d/m/Y H:i') }}</div>
                        <div>Kasir: {{ $sale->creator?->name ?? '-' }}</div>
                    </div>

                    <div class="border-t border-dashed border-gray-300"></div>

                    <div class="text-left space-y-1 my-2">
                        @foreach($sale->items as $item)
                            <div class="flex justify-between text-[10px]">
                                <div class="flex-1">
                                    <span>{{ $item->product->nama }}</span>
                                    <span class="text-gray-500"> x{{ $item->qty }}</span>
                                </div>
                                <span class="tabular-nums ml-2">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-dashed border-gray-300"></div>

                    <div class="text-left space-y-0.5 mt-2 text-[10px]">
                        <div class="flex justify-between"><span>Subtotal</span><span class="tabular-nums">Rp{{ number_format($sale->subtotal, 0, ',', '.') }}</span></div>
                        @if($sale->discount > 0)<div class="flex justify-between text-red-500"><span>Diskon</span><span class="tabular-nums">-Rp{{ number_format($sale->discount, 0, ',', '.') }}</span></div>@endif
                        @if($sale->tax > 0)<div class="flex justify-between"><span>Pajak</span><span class="tabular-nums">Rp{{ number_format($sale->tax, 0, ',', '.') }}</span></div>@endif
                    </div>

                    <div class="border-t border-dashed border-gray-300 my-2"></div>
                    <div class="flex justify-between font-bold text-sm">
                        <span>TOTAL</span>
                        <span class="tabular-nums">Rp{{ number_format($sale->total, 0, ',', '.') }}</span>
                    </div>

                    <div class="mt-4 text-[10px] text-gray-500">Terima kasih telah berbelanja</div>
                </div>

                {{-- Actions --}}
                <div class="px-6 pb-4 flex gap-2">
                    <button onclick="window.print()" class="flex-1 rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        🖨️ Print
                    </button>
                    <button wire:click="closeReceipt" class="flex-1 rounded-lg bg-gray-900 dark:bg-white text-white dark:text-gray-900 px-4 py-2.5 text-sm font-medium hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Auto-focus scanner on page load and interaction --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            const focusScanner = () => {
                const el = document.querySelector('[x-ref="scanner"]');
                if (el) setTimeout(() => el.focus(), 100);
            };
            focusScanner();
            document.addEventListener('click', focusScanner);
            Livewire.hook('morph.updated', focusScanner);
        });
    </script>
</x-filament-panels::page>
