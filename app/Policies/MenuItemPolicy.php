<?php

namespace App\Policies;

use App\Models\MenuItem;
use App\Models\User;

class MenuItemPolicy
{
    public function viewAny(User $usuario): bool
    {
        return true;
    }

    public function view(User $usuario, MenuItem $menuItem): bool
    {
        return true;
    }

    public function create(User $usuario): bool
    {
        return true;
    }

    public function update(User $usuario, MenuItem $menuItem): bool
    {
        return true;
    }

    public function reorder(User $usuario): bool
    {
        return true;
    }

    public function delete(User $usuario, MenuItem $menuItem): bool
    {
        return true;
    }

    public function restore(User $usuario, MenuItem $menuItem): bool
    {
        return true;
    }
}
