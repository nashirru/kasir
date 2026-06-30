<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToOutlet
{
    protected static function bootBelongsToOutlet(): void
    {
        static::addGlobalScope('outlet', function (Builder $builder) {
            $user = Auth::user();
            if (! $user) {
                return;
            }

            // Admin pusat melihat semua outlet
            if ($user->hasRole('admin')) {
                return;
            }

            if ($user->outlet_id) {
                $builder->where('outlet_id', $user->outlet_id);
            }
        });
    }
}
