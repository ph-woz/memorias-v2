<?php

namespace App\Policies;

use App\Models\Categoria;
use App\Models\User;

class CategoriaPolicy
{
    public function viewAny(User $usuario): bool
    {
        return true;
    }

    public function view(User $usuario, Categoria $categoria): bool
    {
        return true;
    }

    public function create(User $usuario): bool
    {
        return true;
    }

    public function update(User $usuario, Categoria $categoria): bool
    {
        return true;
    }

    public function delete(User $usuario, Categoria $categoria): bool
    {
        return true;
    }
}
