<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manajer', 'kasir', 'staff_gudang']);
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasAnyRole(['admin', 'manajer', 'kasir', 'staff_gudang']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manajer']);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasAnyRole(['admin', 'manajer']);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasRole('admin');
    }
}
