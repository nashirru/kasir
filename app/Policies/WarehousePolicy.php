<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manajer']);
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->hasAnyRole(['admin', 'manajer']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manajer']);
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->hasAnyRole(['admin', 'manajer']);
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->hasRole('admin');
    }
}
