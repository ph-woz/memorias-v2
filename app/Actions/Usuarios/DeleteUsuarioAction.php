<?php

namespace App\Actions\Usuarios;

use App\Models\User;
use RuntimeException;

class DeleteUsuarioAction
{
    public function execute(User $usuario, ?User $ator = null): void
    {
        if ($ator !== null && $ator->id === $usuario->id) {
            throw new RuntimeException('Você não pode excluir a própria conta por aqui.');
        }

        $usuario->delete();
    }
}
