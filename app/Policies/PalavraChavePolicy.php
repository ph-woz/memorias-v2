<?php

namespace App\Policies;

use App\Models\PalavraChave;
use App\Models\User;

class PalavraChavePolicy
{
    public function viewAny(User $usuario): bool
    {
        return true;
    }

    public function view(User $usuario, PalavraChave $palavraChave): bool
    {
        return true;
    }

    public function create(User $usuario): bool
    {
        return true;
    }

    public function update(User $usuario, PalavraChave $palavraChave): bool
    {
        return true;
    }

    public function delete(User $usuario, PalavraChave $palavraChave): bool
    {
        return true;
    }

    public function restore(User $usuario, PalavraChave $palavraChave): bool
    {
        return true;
    }
}
