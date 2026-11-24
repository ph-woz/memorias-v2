<?php

namespace App\Policies;

use App\Models\Artigo;
use App\Models\User;

class ArtigoPolicy
{
    public function viewAny(User $usuario): bool
    {
        return true;
    }

    public function view(User $usuario, Artigo $artigo): bool
    {
        return true;
    }

    public function create(User $usuario): bool
    {
        return true;
    }

    public function update(User $usuario, Artigo $artigo): bool
    {
        return true;
    }

    public function delete(User $usuario, Artigo $artigo): bool
    {
        return true;
    }

    public function restore(User $usuario, Artigo $artigo): bool
    {
        return true;
    }
}
