<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $usuario): bool
    {
        return true;
    }

    public function view(User $usuario, User $alvo): bool
    {
        return true;
    }

    public function create(User $usuario): bool
    {
        return true;
    }

    public function update(User $usuario, User $alvo): bool
    {
        return true;
    }

    public function delete(User $usuario, User $alvo): bool
    {
        return $usuario->id !== $alvo->id;
    }
}
