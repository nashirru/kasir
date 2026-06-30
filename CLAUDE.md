# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Aplikasi Kasir (POS) + Manajemen Gudang Multi-Cabang** — Built with Laravel 12 + Filament PHP 3.3. Multi-outlet POS system with warehouse/inventory management, purchase orders, stock transfers, cash register shifts, and reporting.

## Key Commands

### Setup & Dev Server
```bash
# Full setup from scratch
composer install && cp .env.example .env && php artisan key:generate && php artisan migrate --seed && npm install && npm run build

# Create DB + storage link first (for XAMPP):
php artisan storage:link

# Development server (runs server + queue + logs + Vite concurrently)
composer dev

# Single server only
php artisan serve
```

### Database
```bash
# Fresh migrate + seed (destroys all data)
php artisan migrate:fresh --seed

# Migrate only
php artisan migrate --seed
```

### Frontend
```bash
npm run dev    # Vite dev (hot reload)
npm run build  # Production build
```

### Testing
```bash
composer test   # Runs php artisan config:clear + phpunit
# or directly:
php artisan test
```

### Other
```bash
php artisan storage:link    # Create public/storage symlink
php artisan queue:listen    # Process queue jobs
php artisan pail            # Tail log file (dev only)
php artisan optimize:clear  # Clear all cached config/routes/views
```

## Architecture

### Stack
- **Laravel 12** + **Filament v3.3** (custom Livewire pages for POS)
- **MySQL** (default DB — config in .env; .env.example uses sqlite for testing)
- **Session/Cache/Queue**: database driver
- **Vite + Tailwind CSS v4**
- **Timezone**: default UTC (update `config/app.php` `'timezone' => 'Asia/Jakarta'` for Indonesian usage)

### Role System (spatie/laravel-permission)
5 roles enforced via Spatie Permission gates/policies:
- **admin** — Full access: all outlets, all CRUD, consolidated reports
- **manajer** — Full access for assigned outlet only
- **kasir** — POS page, cash register shifts, own transaction history
- **staff_gudang** — Inventory modules only (stock, transfer, opname, goods receipt)

### Multi-Cabang Architecture
- Every `User` has `outlet_id` (nullable for admin).
- `BelongsToOutlet` trait (global scope) auto-filters queries by the logged-in user's `outlet_id`, skipped for admin role.
- `Warehouse` belongs to one `Outlet`; an outlet can have multiple warehouses.
- Stock is NEVER implicitly merged across outlets — inter-warehouse transfers require `StockTransfer` with approval flow.

### Filament Panels
- **Admin Panel** (`/admin`) — All resources + dashboard, for admin/manajer roles. Navigation grouped: Master Data, Inventory, Purchasing, Sales, Reports, Settings.
- **Kasir Panel** (`/kasir`) — PointOfSale, CashRegisterShift, and own transaction history. Separate PanelProvider.

### Models & Modules

#### Master Data
- `Outlet` → `Warehouse` (outlet_id, type: utama/cadangan)
- `User` (outlet_id + Spatie roles)
- `Category`, `Product` (barcode unique nullable, base_unit_id, harga_beli, harga_jual, gambar)
- `ProductUnit`, `ProductUnitConversion` (conversion_qty to base unit)
- `Supplier`, `Customer`

#### Inventory & Stock
- `Stock` (product_id, warehouse_id, qty in base unit — unique constraint)
- `StockMovement` (immutable audit log: in/out/transfer/adjustment/opname)
- `ProductBatch` (batch_number, expired_date, qty) — FEFO source of truth
- `StockTransfer` + `StockTransferItem` (pending → in_transit → received / rejected)
- `StockOpname` + `StockOpnameItem` (qty_system vs qty_fisik → adjustment)

#### Purchasing
- `PurchaseOrder` + `PurchaseOrderItem` (status: draft/ordered/partial/completed/cancelled)
- `GoodsReceipt` + `GoodsReceiptItem` (partial receive, auto-create ProductBatch + addStock)

#### POS & Sales
- `CashRegister` + `CashRegisterTransaction` (open/close shift, kas masuk/keluar)
- `Sale` + `SaleItem` + `SalePayment` (split payment support)
- `SaleReturn` + `SaleReturnItem`

#### Finance
- `ExpenseCategory`, `Expense` (outlet-scoped operational expenses)
- `ActivityLog` (spatie/laravel-activitylog audit trail)

### Key Services (app/Services/)
- **StockService** — addStock, deductStock with **FEFO** (ProductBatch sorted by expired_date ASC). All operations wrapped in DB::transaction().
- **SaleService** — createSale (cart → stock deduction → Sale + SaleItem + SalePayment), voidSale (restore stock), processReturn (return to original batch or new batch).
- **PurchaseService** — createPurchaseOrder, receiveGoods (partial/full, calls StockService::addStock).
- **StockTransferService** — approve (deduct source), receive (add target), reject (restore if in_transit).
- **UnitConversionService** — toBaseUnit, fromBaseUnit for any ProductUnit chain.
- **BarcodeService** — generate (CODE128, prefix INT-), findByBarcode lookup.
- **ReportService** — salesReport, stockReport, profitReport, stockCard.

### Key Business Logic
- **FEFO (First Expired First Out)**: deductStock picks ProductBatch with nearest expired_date first; splits across batches if quantity insufficient.
- **Unit Conversion**: Stock always in base unit. Conversions via ProductUnitConversion before touching Stock table.
- **Stock Transfer Flow**: pending → in_transit (source deducted) → received (target added) / rejected (source restored).
- **Cash Register**: Must open shift (input opening balance) before POS transactions. Closing computes expected vs actual cash, records discrepancy.

### Policies
Resource-based policies for CRUD gating: `CategoryPolicy`, `OutletPolicy`, `ProductPolicy`, `ProductUnitPolicy`, `UserPolicy`, `WarehousePolicy`.

### Folder Structure
```
app/
├── Enums/           # SaleStatus, StockMovementType
├── Filament/
│   ├── Resources/   # 12+ Filament resources with Form/Tables
│   ├── Pages/       # PointOfSale.php (Livewire), CashRegisterShift.php
│   └── Widgets/     # SalesChart, LowStockWidget, TopProductsWidget
├── Models/          # 20+ Eloquent models
├── Policies/        # 6 authorization policies
├── Providers/
│   └── Filament/    # AdminPanelProvider (/admin), KasirPanelProvider (/kasir)
├── Services/        # 7 service classes (business logic layer)
└── Traits/          # BelongsToOutlet (multi-cabang global scope)
```

## Known Issues / Gotchas
- **No KasirPanelProvider implemented yet** — `/kasir` panel still needs setup (PointOfSale page exists but panel provider may be incomplete).
- **Package exports**: `maatwebsite/excel` and `barryvdh/laravel-dompdf` are in composer.json require (not require-dev) — run `composer install` to get them.
- **FEFO** requires ProductBatch with expired_date to work correctly; products without batches will fail on deductStock.
- **PointOfSale** uses `belongsToOutlet` scope — user must have `outlet_id` set to use POS.
- **Barcode** field on Product is unique nullable — empty string or duplicate barcodes will fail at DB level.
- **Database driver for session/cache/queue**: requires running `php artisan queue:listen` for background jobs; `composer dev` handles this via concurrently.
