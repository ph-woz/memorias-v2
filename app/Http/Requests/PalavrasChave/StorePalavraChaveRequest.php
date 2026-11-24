<?php

namespace App\Http\Requests\PalavrasChave;

use App\Models\PalavraChave;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nome' => 'nome',
        ];
    }
}
