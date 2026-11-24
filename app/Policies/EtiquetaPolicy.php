<?php

namespace App\Policies;

use App\Models\Etiqueta;
use App\Models\User;

class EtiquetaPolicy
{
    public function viewAny(User $usuario): bool
    {
        return true;
    }

    public function view(User $usuario, Etiqueta $etiqueta): bool
    {
        return true;
    }

    public function create(User $usuario): bool
    {
        return true;
    }

    public function update(User $usuario, Etiqueta $etiqueta): bool
    {
        return true;
    }

    public function delete(User $usuario, Etiqueta $etiqueta): bool
    {
        return true;
    }

    public function restore(User $usuario, Etiqueta $etiqueta): bool
    {
        return true;
    }
}
