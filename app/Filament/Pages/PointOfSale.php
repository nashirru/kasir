<?php

namespace App\Filament\Pages;

use App\Models\CashRegister;
use App\Models\Product;
use App\Models\ProductUnit;
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
    public float $taxPercent = 0;
    public array $payments = [];

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

    public function scanBarcode(BarcodeService $barcodeService): void
    {
        $code = trim($this->barcodeInput);
        if (empty($code)) return;

        $product = $barcodeService->findByBarcode($code);
        if (! $product || ! $product->is_active) {
            Notification::make()
                ->title('Barcode tidak terdaftar')
                ->warning()
                ->send();
            $this->barcodeInput = '';
            return;
        }

        $this->addToCart($product->id);
        $this->barcodeInput = '';
    }

    public function addToCart(int $productId): void
    {
        $product = Product::find($productId);
        if (! $product || ! $product->is_active) return;

        $key = (string) $productId;

        if (isset($this->cart[$key])) {
            $this->cart[$key]['qty'] += 1;
        } else {
            $this->cart[$key] = [
                'product_id' => $product->id,
                'nama' => $product->nama,
                'price' => (float) $product->harga_jual,
                'qty' => 1,
                'unit_id' => $product->base_unit_id,
                'unit_name' => $product->baseUnit?->nama ?? 'pcs',
            ];
        }
    }

    public function updateQty(int $productId, float $qty): void
    {
        $key = (string) $productId;
        if (isset($this->cart[$key])) {
            if ($qty <= 0) {
                unset($this->cart[$key]);
            } else {
                $this->cart[$key]['qty'] = $qty;
            }
        }
    }

    public function removeItem(int $productId): void
    {
        unset($this->cart[(string) $productId]);
    }

    public function getCartSubtotalProperty(): float
    {
        return collect($this->cart)->sum(fn ($item) => $item['qty'] * $item['price']);
    }

    public function getTotalProperty(): float
    {
        $subtotal = $this->getCartSubtotalProperty();
        $discountAmount = $subtotal * ($this->discount / 100);
        $taxAmount = ($subtotal - $discountAmount) * ($this->taxPercent / 100);
        return $subtotal - $discountAmount + $taxAmount;
    }

    public function getProductsProperty()
    {
        $query = Product::where('is_active', true)->with('category', 'baseUnit');

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

        return $query->paginate(12);
    }

    public function checkout(SaleService $saleService): void
    {
        if (empty($this->cart)) {
            Notification::make()->title('Keranjang kosong')->warning()->send();
            return;
        }

        $user = Auth::user();
        $warehouse = Warehouse::where('outlet_id', $user->outlet_id)->where('tipe', 'utama')->first();
        if (! $warehouse) {
            Notification::make()->title('Tidak ada gudang utama untuk outlet ini')->danger()->send();
            return;
        }

        $items = [];
        foreach ($this->cart as $item) {
            $items[] = [
                'product_id' => $item['product_id'],
                'unit_id' => $item['unit_id'],
                'qty' => $item['qty'],
                'harga_satuan' => $item['price'],
            ];
        }

        $subtotal = $this->getCartSubtotalProperty();
        $discountAmount = $subtotal * ($this->discount / 100);
        $taxAmount = ($subtotal - $discountAmount) * ($this->taxPercent / 100);

        $payments = $this->payments;
        if (empty($payments)) {
            $payments = [['method' => 'tunai', 'amount' => $this->getTotalProperty()]];
        }

        try {
            $sale = $saleService->createSale([
                'outlet_id' => $user->outlet_id,
                'warehouse_id' => $warehouse->id,
                'cash_register_id' => $this->cashRegister->id,
                'items' => $items,
                'discount' => $discountAmount,
                'tax' => $taxAmount,
                'payments' => $payments,
            ]);

            $this->cart = [];
            $this->discount = 0;
            $this->taxPercent = 0;
            $this->payments = [];

            session()->flash('receipt', $sale->load('items.product', 'payments', 'outlet'));

            Notification::make()
                ->title("Transaksi {$sale->invoice_number} berhasil!")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Transaksi gagal: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
