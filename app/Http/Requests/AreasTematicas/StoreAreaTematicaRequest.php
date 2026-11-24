<?php

namespace App\Http\Requests\AreasTematicas;

use App\Models\AreaTematica;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAreaTematicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AreaTematica::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('areas_tematicas', 'slug'),
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
            'slug' => 'slug',
        ];
    }
}
