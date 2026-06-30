<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    protected $fillable = ['outlet_id', 'user_id', 'opening_balance', 'closing_balance', 'opened_at', 'closed_at', 'status'];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CashRegisterTransaction::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function totalSales(): float
    {
        return (float) $this->sales()->where('status', 'completed')->sum('total');
    }

    public function totalCashInOut(): float
    {
        $in = (float) $this->transactions()->where('type', 'in')->sum('amount');
        $out = (float) $this->transactions()->where('type', 'out')->sum('amount');
        return $in - $out;
    }

    public function expectedBalance(): float
    {
        return (float) $this->opening_balance + $this->totalSales() + $this->totalCashInOut();
    }
}
