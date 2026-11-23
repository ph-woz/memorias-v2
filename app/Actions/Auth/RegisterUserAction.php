<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;

class RegisterUserAction
{
    /**
     * @param  array{nome: string, nickname: string, email: string, password: string}  $data
     */
    public function execute(array $data): User
    {
        $user = User::query()->create([
            'nome' => $data['nome'],
            'nickname' => $data['nickname'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        event(new Registered($user));

        Auth::login($user);

        return $user;
    }
}
