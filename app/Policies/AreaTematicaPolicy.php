<?php

namespace App\Policies;

use App\Models\AreaTematica;
use App\Models\User;

class AreaTematicaPolicy
{
    public function viewAny(User $usuario): bool
    {
        return true;
    }

    public function view(User $usuario, AreaTematica $areaTematica): bool
    {
        return true;
    }

    public function create(User $usuario): bool
    {
        return true;
    }

    public function update(User $usuario, AreaTematica $areaTematica): bool
    {
        return true;
    }

    public function delete(User $usuario, AreaTematica $areaTematica): bool
    {
        return true;
    }

    public function restore(User $usuario, AreaTematica $areaTematica): bool
    {
        return true;
    }
}
