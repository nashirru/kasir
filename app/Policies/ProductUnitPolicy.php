<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ProductUnit;

class ProductUnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manajer']);
    }

    public function view(User $user, ProductUnit $productUnit): bool
    {
        return $user->hasAnyRole(['admin', 'manajer']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manajer']);
    }

    public function update(User $user, ProductUnit $productUnit): bool
    {
        return $user->hasAnyRole(['admin', 'manajer']);
    }

    public function delete(User $user, ProductUnit $productUnit): bool
    {
        return $user->hasRole('admin');
    }
}
