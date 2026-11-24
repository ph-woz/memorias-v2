<?php

namespace App\Http\Requests\Usuarios;

use App\Enums\StatusUsuario;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('usuario')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nickname')) {
            $this->merge([
                'nickname' => strtolower((string) $this->input('nickname')),
            ]);
        }

    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $usuario */
        $usuario = $this->route('usuario');

        return [
            'nome' => ['required', 'string', 'max:255'],
            'nickname' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'alpha_dash:ascii',
                Rule::unique(User::class, 'nickname')->ignore($usuario->id),
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($usuario->id),
            ],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'status' => ['required', 'integer', Rule::enum(StatusUsuario::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nome' => 'nome',
            'nickname' => 'nickname',
            'email' => 'e-mail',
            'password' => 'senha',
            'status' => 'status',
        ];
    }
}
