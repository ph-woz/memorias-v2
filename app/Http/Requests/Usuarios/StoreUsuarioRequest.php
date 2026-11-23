<?php

namespace App\Http\Requests\Usuarios;

use App\Enums\StatusUsuario;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nickname')) {
            $this->merge([
                'nickname' => strtolower((string) $this->input('nickname')),
            ]);
        }

        $this->merge([
            'email_verificado' => $this->boolean('email_verificado'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'nickname' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'alpha_dash:ascii',
                Rule::unique(User::class, 'nickname'),
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class, 'email'),
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
            'email_verificado' => ['nullable', 'boolean'],
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
            'email_verificado' => 'e-mail verificado',
            'status' => 'status',
        ];
    }
}

