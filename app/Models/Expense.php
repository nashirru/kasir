<?php

namespace App\Models;

use App\Traits\BelongsToOutlet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use BelongsToOutlet;

    protected $fillable = ['expense_category_id', 'outlet_id', 'amount', 'deskripsi', 'tanggal', 'created_by'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'tanggal' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}
