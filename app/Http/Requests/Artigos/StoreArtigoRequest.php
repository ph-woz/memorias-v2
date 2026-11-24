<?php

namespace App\Http\Requests\Artigos;

use App\Enums\StatusArtigo;
use App\Http\Requests\Artigos\Concerns\NormalizaDadosArtigo;
use App\Http\Requests\Artigos\Concerns\ValidaDocumentosArtigo;
use App\Models\Artigo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreArtigoRequest extends FormRequest
{
    use NormalizaDadosArtigo;
    use ValidaDocumentosArtigo;

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
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('artigos', 'slug'),
            ],
            'doi' => ['nullable', 'string', 'max:255'],
            'resumo' => ['nullable', 'string', 'max:5000'],
            'conteudo' => ['required', 'string'],
            'endereco_instituicoes' => ['nullable', 'string'],
            'autor_correspondente' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(StatusArtigo::class)],
            'categorias' => ['nullable', 'array'],
            'categorias.*' => ['integer', 'exists:categorias,id'],
            'autores' => ['required', 'array', 'min:1'],
            'autores.*' => ['required', 'string', 'max:255'],
            'palavras_chave' => ['nullable', 'array'],
            'palavras_chave.*' => ['required', 'string', 'max:255'],
            'areas_tematicas' => ['nullable', 'array'],
            'areas_tematicas.*' => ['required', 'string', 'max:255'],
            'etiquetas' => ['nullable', 'array'],
            'etiquetas.*' => ['required', 'string', 'max:255'],
            'referencias' => ['nullable', 'array'],
            'referencias.*' => ['required', 'string', 'max:2000'],
            ...$this->regrasDocumentos(),
            'seo_titulo' => ['nullable', 'string', 'max:70'],
            'seo_descricao' => ['nullable', 'string', 'max:160'],
            'seo_palavras_chave' => ['nullable', 'string', 'max:255'],
            'publicado_em' => ['nullable', 'date'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'titulo' => 'título',
            'slug' => 'slug',
            'doi' => 'DOI',
            'resumo' => 'resumo',
            'conteudo' => 'conteúdo',
            'endereco_instituicoes' => 'endereço das instituições',
            'autor_correspondente' => 'autor correspondente',
            'status' => 'status',
            'categorias' => 'categorias',
            'categorias.*' => 'categoria',
            'autores' => 'autores',
            'autores.*' => 'autor',
            'palavras_chave' => 'palavras-chave',
            'palavras_chave.*' => 'palavra-chave',
            'areas_tematicas' => 'áreas temáticas',
            'areas_tematicas.*' => 'área temática',
            'etiquetas' => 'etiquetas',
            'etiquetas.*' => 'etiqueta',
            'referencias' => 'referências',
            'referencias.*' => 'referência',
            ...$this->atributosDocumentos(),
            'seo_titulo' => 'título SEO',
            'seo_descricao' => 'descrição SEO',
            'seo_palavras_chave' => 'palavras-chave SEO',
            'publicado_em' => 'data de publicação',
            'foto' => 'foto',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator): mixed => $this->validarOrigemDocumentos($validator));
    }
}
