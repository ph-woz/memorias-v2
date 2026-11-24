<?php

namespace App\Policies;

use App\Models\Pagina;
use App\Models\User;

class PaginaPolicy
{
    public function viewAny(User $usuario): bool
    {
        return true;
    }

    public function view(User $usuario, Pagina $pagina): bool
    {
        return true;
    }

    public function create(User $usuario): bool
    {
        return true;
    }

    public function update(User $usuario, Pagina $pagina): bool
    {
        return true;
    }

    public function delete(User $usuario, Pagina $pagina): bool
    {
        return true;
    }

    public function restore(User $usuario, Pagina $pagina): bool
    {
        return true;
    }
}
