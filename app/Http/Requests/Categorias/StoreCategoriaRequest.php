<?php

namespace App\Http\Requests\Categorias;

use App\Models\Categoria;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Categoria::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255', Rule::unique('categorias', 'nome')],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categorias', 'slug'),
            ],
            'foto' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'seo_titulo' => ['nullable', 'string', 'max:70'],
            'seo_descricao' => ['nullable', 'string', 'max:160'],
            'seo_palavras_chave' => ['nullable', 'string', 'max:255'],
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
            'foto' => 'foto',
            'seo_titulo' => 'título SEO',
            'seo_descricao' => 'descrição SEO',
            'seo_palavras_chave' => 'palavras-chave SEO',
        ];
    }
}
