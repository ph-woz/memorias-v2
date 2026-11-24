<?php

namespace App\Http\Requests\Etiquetas;

use App\Models\Etiqueta;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEtiquetaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('etiqueta')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Etiqueta $etiqueta */
        $etiqueta = $this->route('etiqueta');

        return [
            'nome' => ['required', 'string', 'max:255', Rule::unique('etiquetas', 'nome')->ignore($etiqueta->id)],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('etiquetas', 'slug')->ignore($etiqueta->id),
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
