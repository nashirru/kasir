<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductUnit;
use App\Models\ProductUnitConversion;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\CashRegister;
use App\Services\BarcodeService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@kasir.test')->first();
        $kasir = User::where('email', 'kasir@kasir.test')->first() ?? $admin;
        $userId = $admin?->id ?? 1;

        $outlet = \App\Models\Outlet::first();
        if (!$outlet) {
            $this->command->error('Jalankan UserSeeder dulu sebelum DemoDataSeeder!');
            return;
        }
        $warehouse = Warehouse::where('outlet_id', $outlet->id)->first();

        // ============================================================
        // 1. PRODUCT UNITS
        // ============================================================
        $unitData = [
            'Pcs' => 'pcs', 'Box' => 'box', 'Lusin' => 'lsn', 'Dus' => 'dus',
            'Kg' => 'kg', 'Liter' => 'ltr', 'Pack' => 'pack', 'Botol' => 'btl',
            'Sachet' => 'sct', 'Karton' => 'krn', 'Renceng' => 'rcn',
        ];
        $units = [];
        foreach ($unitData as $nama => $singkatan) {
            $units[$nama] = ProductUnit::firstOrCreate(['nama' => $nama], compact('nama', 'singkatan'));
        }

        // ============================================================
        // 2. CATEGORIES
        // ============================================================
        $catTree = [
            'Makanan' => ['Makanan Ringan', 'Makanan Instan', 'Makanan Kaleng'],
            'Minuman' => ['Air Mineral', 'Minuman Bersoda', 'Minuman Kemasan', 'Kopi & Teh'],
            'Sembako' => ['Beras & Tepung', 'Minyak & Bumbu', 'Gula & Susu'],
            'Alat Tulis' => [],
            'Kebersihan' => ['Pembersih Rumah', 'Perawatan Diri'],
        ];
        $cats = [];
        foreach ($catTree as $parentName => $children) {
            $parent = Category::firstOrCreate(['nama' => $parentName], ['parent_id' => null]);
            $cats[$parentName] = $parent;
            foreach ($children as $child) {
                $cats[$child] = Category::firstOrCreate(['nama' => $child], ['parent_id' => $parent->id]);
            }
        }

        // ============================================================
        // 3. PRODUCTS + STOCK + BATCHES
        // ============================================================
        $barcodeService = app(BarcodeService::class);

        $productDefs = [
            ['Indomie Goreng',         'Makanan Instan',   'Pcs',    2500,  3500,  500],
            ['Indomie Kuah Soto',      'Makanan Instan',   'Pcs',    2500,  3500,  480],
            ['Mie Sedap Goreng',      'Makanan Instan',   'Pcs',    2500,  3500,  450],
            ['Bimoli 1L',             'Minyak & Bumbu',   'Botol', 13000, 16000,   50],
            ['Minyakita 1L',          'Minyak & Bumbu',   'Botol', 13500, 16500,   40],
            ['Beras Sania 5Kg',       'Beras & Tepung',   'Pcs',   62000, 70000,   30],
            ['Beras Ramos 5Kg',       'Beras & Tepung',   'Pcs',   64000, 72000,   25],
            ['Tepung Terigu Segitiga Biru 1Kg', 'Beras & Tepung', 'Pcs', 11000, 13500, 60],
            ['Gula Pasir Gulaku 1Kg', 'Gula & Susu',      'Pcs',   15000, 18000,   80],
            ['Frisian Flag Kental Manis', 'Gula & Susu',  'Sachet', 4500,  5500,  200],
            ['Kopi Kapal Api 50gr',   'Kopi & Teh',        'Pcs',    5500,  7500,  150],
            ['Teh Sariwangi 50gr',    'Kopi & Teh',        'Pcs',    8000, 10500,  100],
            ['Kecap Manis Bango 275ml','Minyak & Bumbu',   'Botol',  8000, 10500,   70],
            ['Saos Sambal Indofood 275ml', 'Minyak & Bumbu', 'Botol', 7500,  9500,   60],
            ['Aqua 600ml',            'Air Mineral',       'Botol',  2500,  3500,  300],
            ['Le Minerale 600ml',     'Air Mineral',       'Botol',  2500,  3500,  280],
            ['Coca Cola 390ml',       'Minuman Bersoda',   'Botol',  4500,  6000,  120],
            ['Fanta Strawberry 390ml','Minuman Bersoda',   'Botol',  4500,  6000,  100],
            ['Sprite 390ml',          'Minuman Bersoda',   'Botol',  4500,  6000,  100],
            ['Pocari Sweat 500ml',    'Minuman Kemasan',   'Botol',  5500,  7500,   90],
            ['Teh Botol Sosro 500ml', 'Minuman Kemasan',   'Botol',  4500,  6000,  120],
            ['Good Day Cappuccino',   'Kopi & Teh',        'Sachet', 1500,  2500,  400],
            ['Telur Ayam 1Kg',        'Beras & Tepung',    'Pcs',   25000, 30000,   40],
            ['Kornet Pronas 200gr',   'Makanan Kaleng',    'Pcs',   15000, 19000,   35],
            ['Sarden ABC 155gr',      'Makanan Kaleng',    'Pcs',   12000, 15500,   40],
            ['Minyak Goreng Bimoli 2L','Minyak & Bumbu',   'Botol', 24000, 29000,   30],
            ['Garam Halus Refina 500gr','Minyak & Bumbu',  'Pcs',    3500,  5000,   80],
            ['Buku Tulis Sidu 38 Lbr','Alat Tulis',        'Pcs',    3000,  5000,  150],
            ['Pulpen Standard AE7',   'Alat Tulis',        'Pcs',    2000,  3500,  200],
            ['Pensil 2B Joyko',       'Alat Tulis',        'Pcs',    1500,  2500,  180],
            ['Penghapus Joyko',       'Alat Tulis',        'Pcs',    1000,  2000,  100],
            ['Penggaris 30cm',        'Alat Tulis',        'Pcs',    2000,  3500,   80],
            ['Sabun Lifebuoy 75gr',   'Perawatan Diri',    'Pcs',    3000,  4500,  120],
            ['Pepsodent 75gr',        'Perawatan Diri',    'Pcs',    5000,  7500,  100],
            ['Sunsilk Shampoo 70ml',  'Perawatan Diri',    'Botol',  2500,  4000,   90],
            ['Sunlight 450ml',        'Pembersih Rumah',   'Botol',  7000,  9500,   60],
            ['So Klin Lantai 800ml',  'Pembersih Rumah',   'Botol',  8000, 11000,   50],
        ];

        $products = [];
        foreach ($productDefs as $i => [$nama, $catName, $unitName, $hargaBeli, $hargaJual, $stokQty]) {
            $product = Product::create([
                'nama'         => $nama,
                'sku'          => 'SKU-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'category_id'  => $cats[$catName]->id ?? null,
                'base_unit_id' => $units[$unitName]->id,
                'harga_beli'   => $hargaBeli,
                'harga_jual'   => $hargaJual,
                'is_active'    => true,
            ]);
            $barcodeService->generate($product);
            $products[] = $product;

            // Stock
            Stock::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'qty' => $stokQty,
            ]);

            // Batch
            ProductBatch::create([
                'product_id'   => $product->id,
                'warehouse_id' => $warehouse->id,
                'batch_number' => 'BTH-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'expired_date' => Carbon::now()->addMonths(rand(3, 18)),
                'qty'          => $stokQty,
            ]);

            StockMovement::create([
                'product_id'     => $product->id,
                'warehouse_id'   => $warehouse->id,
                'type'           => 'in',
                'qty'            => $stokQty,
                'reference_type' => 'seed',
                'reference_id'   => 0,
                'notes'          => 'Stok awal',
                'created_by'     => $userId,
            ]);
        }

        // -- Unit Conversions --
        $conversions = [
            [0,  'Dus', 40],   // Indomie Goreng: 1 dus = 40 pcs
            [1,  'Dus', 40],   // Indomie Kuah: 1 dus = 40 pcs
            [14, 'Dus', 24],   // Aqua: 1 dus = 24 botol
        ];
        foreach ($conversions as [$prodIdx, $unitName, $qty]) {
            if (isset($products[$prodIdx], $units[$unitName])) {
                ProductUnitConversion::create([
                    'product_id'     => $products[$prodIdx]->id,
                    'unit_id'        => $units[$unitName]->id,
                    'conversion_qty' => $qty,
                ]);
            }
        }

        // ============================================================
        // 4. SUPPLIERS & CUSTOMERS
        // ============================================================
        foreach ([
            ['PT Indofood Sukses Makmur', '021-5550001', 'Jakarta'],
            ['PT Unilever Indonesia',      '021-5550002', 'Tangerang'],
            ['PT Wings Surya',             '021-5550003', 'Surabaya'],
            ['PT Aqua Golden Mississippi', '021-5550004', 'Depok'],
            ['CV Beras Sejahtera',         '022-4440001', 'Bandung'],
            ['PT Mayora Indah',            '021-5550005', 'Jakarta Pusat'],
        ] as [$nama, $telp, $alamat]) {
            Supplier::create(['nama' => $nama, 'telepon' => $telp, 'alamat' => $alamat]);
        }

        foreach ([
            ['Budi Santoso',   '08121111111', 'Jl. Merdeka No. 1'],
            ['Siti Nurhaliza', '08122222222', 'Jl. Sudirman No. 2'],
            ['Ahmad Fauzi',    '08123333333', 'Jl. Gatot Subroto No. 3'],
            ['Dewi Lestari',   '08124444444', 'Jl. Thamrin No. 4'],
            ['Rudi Hermawan',  '08125555555', 'Jl. Kuningan No. 5'],
        ] as [$nama, $telp, $alamat]) {
            Customer::create(['nama' => $nama, 'telepon' => $telp, 'alamat' => $alamat]);
        }

        // ============================================================
        // 5. PURCHASE ORDERS + GOODS RECEIPT
        // ============================================================
        $suppliers = Supplier::all();

        // PO1 — completed
        $po1 = PurchaseOrder::create([
            'supplier_id'  => $suppliers[0]->id,
            'outlet_id'    => $outlet->id,
            'warehouse_id' => $warehouse->id,
            'status'       => 'completed',
            'total'        => 0,
            'created_by'   => $userId,
        ]);
        $po1items = [
            [$products[0], 'Dus',  5,  2500],
            [$products[3], 'Botol', 10, 13000],
            [$products[5], 'Pcs',  10, 62000],
            [$products[8], 'Pcs',  15, 15000],
        ];
        $total = 0;
        foreach ($po1items as [$p, $un, $q, $h]) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $po1->id,
                'product_id'        => $p->id,
                'unit_id'           => $units[$un]->id,
                'qty'               => $q,
                'harga_satuan'      => $h,
            ]);
            $total += $q * $h;
        }
        $po1->update(['total' => $total]);

        $gr1 = GoodsReceipt::create([
            'purchase_order_id' => $po1->id,
            'received_by'       => $userId,
            'received_at'       => Carbon::now()->subDays(7),
            'notes'             => 'Penerimaan penuh',
        ]);
        foreach ($po1->items as $poItem) {
            GoodsReceiptItem::create([
                'goods_receipt_id'       => $gr1->id,
                'purchase_order_item_id' => $poItem->id,
                'qty_received'           => $poItem->qty,
                'batch_number'           => 'PO1-B' . $poItem->id,
                'expired_date'           => Carbon::now()->addMonths(rand(4, 12)),
            ]);
        }

        // PO2 — completed (sembako)
        $po2 = PurchaseOrder::create([
            'supplier_id'  => $suppliers[3]->id,
            'outlet_id'    => $outlet->id,
            'warehouse_id' => $warehouse->id,
            'status'       => 'completed',
            'total'        => 0,
            'created_by'   => $userId,
        ]);
        $po2items = [
            [$products[14], 'Dus',  10, 2500],
            [$products[22], 'Pcs',  20, 25000],
            [$products[23], 'Pcs',  15, 15000],
            [$products[25], 'Botol', 8, 24000],
        ];
        $total = 0;
        foreach ($po2items as [$p, $un, $q, $h]) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $po2->id,
                'product_id'        => $p->id,
                'unit_id'           => $units[$un]->id,
                'qty'               => $q,
                'harga_satuan'      => $h,
            ]);
            $total += $q * $h;
        }
        $po2->update(['total' => $total]);

        $gr2 = GoodsReceipt::create([
            'purchase_order_id' => $po2->id,
            'received_by'       => $userId,
            'received_at'       => Carbon::now()->subDays(3),
            'notes'             => 'Penerimaan sembako dan minuman',
        ]);
        foreach ($po2->items as $poItem) {
            GoodsReceiptItem::create([
                'goods_receipt_id'       => $gr2->id,
                'purchase_order_item_id' => $poItem->id,
                'qty_received'           => $poItem->qty,
                'batch_number'           => 'PO2-B' . $poItem->id,
                'expired_date'           => Carbon::now()->addMonths(rand(3, 10)),
            ]);
        }

        // PO3 — ordered (masih pending, belum diterima)
        $po3 = PurchaseOrder::create([
            'supplier_id'  => $suppliers[1]->id,
            'outlet_id'    => $outlet->id,
            'warehouse_id' => $warehouse->id,
            'status'       => 'ordered',
            'total'        => 0,
            'created_by'   => $userId,
        ]);
        $po3items = [
            [$products[32], 'Pcs', 50, 3000],
            [$products[33], 'Pcs', 40, 5000],
            [$products[1],  'Dus',  4, 2500],
        ];
        $total = 0;
        foreach ($po3items as [$p, $un, $q, $h]) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $po3->id,
                'product_id'        => $p->id,
                'unit_id'           => $units[$un]->id,
                'qty'               => $q,
                'harga_satuan'      => $h,
            ]);
            $total += $q * $h;
        }
        $po3->update(['total' => $total]);

        // ============================================================
        // 6. EXPENSE CATEGORIES & EXPENSES
        // ============================================================
        $expenseCats = [];
        foreach (['Listrik', 'Air', 'Sewa', 'Gaji', 'Transport', 'Lain-lain'] as $catName) {
            $expenseCats[$catName] = ExpenseCategory::create(['nama' => $catName]);
        }

        $expenseData = [
            ['Listrik',   500000,  'Listrik bulan ini',      Carbon::now()->subDays(5)],
            ['Air',       150000,  'PDAM',                    Carbon::now()->subDays(4)],
            ['Sewa',     2000000,  'Sewa tempat Juni',       Carbon::now()->subDays(2)],
            ['Transport', 100000,  'Kirim barang',            Carbon::now()->subDays(6)],
            ['Gaji',     3000000,  'Gaji karyawan',           Carbon::now()->subDays(1)],
            ['Lain-lain',  75000,  'Keperluan operasional',   Carbon::now()->subDays(3)],
        ];
        foreach ($expenseData as [$cat, $amount, $desc, $date]) {
            Expense::create([
                'expense_category_id' => $expenseCats[$cat]->id,
                'outlet_id'           => $outlet->id,
                'amount'              => $amount,
                'deskripsi'           => $desc,
                'tanggal'             => $date,
                'created_by'          => $userId,
            ]);
        }

        // ============================================================
        // 7. SALES — 30 hari riwayat untuk dashboard
        // ============================================================
        $cashReg = CashRegister::create([
            'outlet_id'       => $outlet->id,
            'user_id'         => $kasir?->id ?? $userId,
            'opening_balance' => 500000,
            'opened_at'       => Carbon::now()->subDays(7),
            'status'          => 'closed',
            'closing_balance' => 3500000,
            'closed_at'       => Carbon::now()->subDays(1),
        ]);

        for ($day = 29; $day >= 0; $day--) {
            $saleDate  = Carbon::now()->subDays($day);
            $numItems  = rand(2, 6);
            $subtotal  = 0;
            $items     = [];

            for ($j = 0; $j < $numItems; $j++) {
                $prod  = $products[array_rand($products)];
                $qty   = rand(1, 5);
                $price = (float) $prod->harga_jual;
                $items[] = [
                    'product_id'   => $prod->id,
                    'unit_id'      => $prod->base_unit_id,
                    'qty'          => $qty,
                    'harga_satuan' => $price,
                    'subtotal'     => $qty * $price,
                ];
                $subtotal += $qty * $price;
            }

            $discount  = rand(0, 1) ? round($subtotal * 0.05, -2) : 0;
            $tax       = round(($subtotal - $discount) * 0.11, -2);
            $total     = $subtotal - $discount + $tax;
            $invoice   = 'INV-' . $saleDate->format('Ymd') . '-' . str_pad($day + 1, 3, '0', STR_PAD_LEFT);

            $sale = Sale::create([
                'outlet_id'        => $outlet->id,
                'warehouse_id'     => $warehouse->id,
                'cash_register_id' => $cashReg->id,
                'invoice_number'   => $invoice,
                'subtotal'         => $subtotal,
                'discount'         => $discount,
                'tax'              => $tax,
                'total'            => $total,
                'status'           => 'completed',
                'created_by'       => $kasir?->id ?? $userId,
                'created_at'       => $saleDate->copy()->addHours(rand(8, 20))->addMinutes(rand(0, 59)),
                'updated_at'       => $saleDate,
            ]);

            foreach ($items as $item) {
                SaleItem::create(array_merge($item, ['sale_id' => $sale->id]));
            }

            SalePayment::create([
                'sale_id'        => $sale->id,
                'payment_method' => 'tunai',
                'amount'         => $total,
            ]);
        }

        $this->command->info('✅ Demo data siap! ' . count($products) . ' produk, 30 hari riwayat penjualan.');
    }
}
