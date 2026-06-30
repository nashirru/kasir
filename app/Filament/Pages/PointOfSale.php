<?php

namespace App\Filament\Pages;

use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\BarcodeService;
use App\Services\SaleService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class PointOfSale extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'POS';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.point-of-sale';

    public ?CashRegister $cashRegister = null;
    public array $cart = [];
    public string $barcodeInput = '';
    public ?string $searchQuery = null;
    public ?int $selectedCategory = null;
    public float $discount = 0;
    public float $taxPercent = 11;
    public string $paymentMethod = 'tunai';

    public function mount(): void
    {
        $user = Auth::user();
        $this->cashRegister = CashRegister::where('user_id', $user->id)
            ->where('outlet_id', $user->outlet_id)
            ->where('status', 'open')
            ->first();

        if (! $this->cashRegister) {
            $this->redirect(CashRegisterShift::getUrl());
        }
    }

    // ─── Barcode Scanner ───────────────────────────────────────

    public function scanBarcode(BarcodeService $barcodeService): void
    {
        $code = trim($this->barcodeInput);
        if (empty($code)) return;

        $product = $barcodeService->findByBarcode($code);
        if (! $product || ! $product->is_active) {
            $this->dispatch('scan-feedback', status: 'not-found');
            Notification::make()
                ->title('Barcode tidak terdaftar')
                ->body("Kode: {$code}")
                ->warning()
                ->send();
            $this->barcodeInput = '';
            return;
        }

        $this->addToCart($product->id);
        $this->dispatch('scan-feedback', status: 'found');
        $this->barcodeInput = '';
    }

    // ─── Cart ──────────────────────────────────────────────────

    public function addToCart(int $productId): void
    {
        $product = Product::find($productId);
        if (! $product || ! $product->is_active) return;

        $key = (string) $productId;

        if (isset($this->cart[$key])) {
            $this->cart[$key]['qty'] += 1;
        } else {
            $this->cart[$key] = [
                'product_id'  => $product->id,
                'nama'        => $product->nama,
                'price'       => (float) $product->harga_jual,
                'qty'         => 1,
                'unit_id'     => $product->base_unit_id,
                'unit_name'   => $product->baseUnit?->nama ?? 'pcs',
                'stok'        => (int) ($product->stok?->first()?->qty ?? 0),
            ];
        }
    }

    public function updateQty(int $productId, float $qty): void
    {
        $key = (string) $productId;
        if (! isset($this->cart[$key])) return;

        if ($qty <= 0) {
            unset($this->cart[$key]);
        } else {
            $this->cart[$key]['qty'] = $qty;
        }
    }

    public function removeItem(int $productId): void
    {
        unset($this->cart[(string) $productId]);
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->discount = 0;
        $this->taxPercent = 11;
        $this->paymentMethod = 'tunai';
    }

    // ─── Computed ──────────────────────────────────────────────

    public function getCartSubtotalProperty(): float
    {
        return collect($this->cart)->sum(fn ($i) => $i['qty'] * $i['price']);
    }

    public function getDiscountAmountProperty(): float
    {
        return $this->getCartSubtotalProperty() * ($this->discount / 100);
    }

    public function getTaxAmountProperty(): float
    {
        $afterDiscount = $this->getCartSubtotalProperty() - $this->getDiscountAmountProperty();
        return $afterDiscount * ($this->taxPercent / 100);
    }

    public function getTotalProperty(): float
    {
        return $this->getCartSubtotalProperty() - $this->getDiscountAmountProperty() + $this->getTaxAmountProperty();
    }

    public function getCartCountProperty(): int
    {
        return collect($this->cart)->sum('qty');
    }

    public function getProductsProperty()
    {
        $query = Product::where('is_active', true)->with('category', 'baseUnit', 'stok');

        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('nama', 'like', "%{$this->searchQuery}%")
                  ->orWhere('barcode', 'like', "%{$this->searchQuery}%")
                  ->orWhere('sku', 'like', "%{$this->searchQuery}%");
            });
        }

        if ($this->selectedCategory) {
            $query->where('category_id', $this->selectedCategory);
        }

        return $query->orderBy('nama')->paginate(20);
    }

    public function getCategoriesProperty()
    {
        return Category::withCount('children')->whereNull('parent_id')->get();
    }

    // ─── Checkout ──────────────────────────────────────────────

    public function checkout(SaleService $saleService): void
    {
        if (empty($this->cart)) {
            Notification::make()->title('Keranjang kosong')->warning()->send();
            return;
        }

        // Server-side validation
        $this->discount = max(0, min(100, (float) $this->discount));
        $this->taxPercent = max(0, min(100, (float) $this->taxPercent));

        $user = Auth::user();
        $warehouse = Warehouse::where('outlet_id', $user->outlet_id)->where('tipe', 'utama')->first();
        if (! $warehouse) {
            Notification::make()->title('Tidak ada gudang utama untuk outlet ini')->danger()->send();
            return;
        }

        $items = [];
        foreach ($this->cart as $item) {
            // Re-fetch fresh price & stock from DB
            $product = Product::with('stok')->find($item['product_id']);
            if (! $product || ! $product->is_active) {
                Notification::make()->title("Produk {$item['nama']} tidak tersedia")->danger()->send();
                return;
            }

            $stok = (int) ($product->stok?->first()?->qty ?? 0);
            if ($stok < $item['qty']) {
                Notification::make()
                    ->title("Stok {$product->nama} tidak mencukupi")
                    ->body("Tersedia: {$stok}, diminta: {$item['qty']}")
                    ->danger()
                    ->send();
                return;
            }

            $items[] = [
                'product_id'   => $product->id,
                'unit_id'      => $item['unit_id'],
                'qty'          => $item['qty'],
                'harga_satuan' => (float) $product->harga_jual,
            ];
        }

        $subtotal      = $this->getCartSubtotalProperty();
        $discountAmount = $this->getDiscountAmountProperty();
        $taxAmount      = $this->getTaxAmountProperty();

        $payments = [['method' => $this->paymentMethod, 'amount' => $this->getTotalProperty()]];

        try {
            $sale = $saleService->createSale([
                'outlet_id'        => $user->outlet_id,
                'warehouse_id'     => $warehouse->id,
                'cash_register_id' => $this->cashRegister->id,
                'items'            => $items,
                'discount'         => $discountAmount,
                'tax'              => $taxAmount,
                'payments'         => $payments,
            ]);

            session()->flash('receipt', $sale->load('items.product', 'payments', 'outlet'));

            $this->clearCart();
            $this->dispatch('cart-cleared');

            Notification::make()
                ->title("Transaksi {$sale->invoice_number} berhasil!")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Transaksi gagal')
                ->body('Silakan coba lagi atau hubungi administrator.')
                ->danger()
                ->send();
        }
    }

    public function closeReceipt(): void
    {
        session()->forget('receipt');
    }
}
