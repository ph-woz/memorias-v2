<?php

namespace App\Actions\Usuarios;

use App\DataTransferObjects\UsuarioData;
use App\Models\User;

class UpdateUsuarioAction
{
    public function execute(User $usuario, UsuarioData $data): User
    {
        $atributos = [
            'nome' => $data->nome,
            'nickname' => $data->nickname,
            'email' => $data->email,
            'email_verified_at' => $data->emailVerificado
                ? ($usuario->email_verified_at ?? now())
                : null,
            'status' => $data->status,
        ];

        if ($data->password !== null) {
            $atributos['password'] = $data->password;
        }

        $usuario->update($atributos);

        return $usuario->refresh();
    }
}
