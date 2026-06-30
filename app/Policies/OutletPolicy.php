<?php

namespace App\Policies;

use App\Models\Outlet;
use App\Models\User;

class OutletPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manajer']);
    }

    public function view(User $user, Outlet $outlet): bool
    {
        return $user->hasAnyRole(['admin', 'manajer']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manajer']);
    }

    public function update(User $user, Outlet $outlet): bool
    {
        return $user->hasAnyRole(['admin', 'manajer']);
    }

    public function delete(User $user, Outlet $outlet): bool
    {
        return $user->hasRole('admin');
    }
}
