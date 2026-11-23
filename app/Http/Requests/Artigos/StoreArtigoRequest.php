<?php

namespace App\Http\Requests\Artigos;

use App\Enums\StatusArtigo;
use App\Models\Artigo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArtigoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Artigo::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'resumo' => ['nullable', 'string', 'max:500'],
            'conteudo' => ['required', 'string'],
            'status' => ['required', Rule::enum(StatusArtigo::class)],
            'categoria_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'autor_ids' => ['required', 'array', 'min:1'],
            'autor_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'palavra_chave_ids' => ['nullable', 'array'],
            'palavra_chave_ids.*' => ['integer', 'exists:palavras_chave,id'],
            'publicado_em' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'titulo' => 'título',
            'resumo' => 'resumo',
            'conteudo' => 'conteúdo',
            'status' => 'status',
            'categoria_id' => 'categoria',
            'autor_ids' => 'autores',
            'palavra_chave_ids' => 'palavras-chave',
            'publicado_em' => 'data de publicação',
        ];
    }
}
