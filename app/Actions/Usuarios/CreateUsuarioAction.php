<?php

namespace App\Actions\Usuarios;

use App\DataTransferObjects\UsuarioData;
use App\Models\User;
use RuntimeException;

class CreateUsuarioAction
{
    public function execute(UsuarioData $data): User
    {
        if ($data->password === null) {
            throw new RuntimeException('A senha do usuário é obrigatória.');
        }

        return User::query()->create([
            'nome' => $data->nome,
            'nickname' => $data->nickname,
            'email' => $data->email,
            'password' => $data->password,
            'email_verified_at' => $data->emailVerificado ? now() : null,
            'status' => $data->status,
        ]);
    }
}
