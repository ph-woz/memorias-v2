<?php

namespace App\Http\Requests\PalavrasChave;

use App\Models\PalavraChave;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePalavraChaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PalavraChave::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'identificador' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('palavras_chave', 'identificador'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nome' => 'nome',
            'identificador' => 'slug',
        ];
    }
}
