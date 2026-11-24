<?php

namespace App\Http\Requests\Etiquetas;

use App\Models\Etiqueta;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEtiquetaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Etiqueta::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255', Rule::unique('etiquetas', 'nome')],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('etiquetas', 'slug'),
            ],
            'cor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nome' => 'nome',
            'slug' => 'slug',
            'cor' => 'cor',
        ];
    }
}
