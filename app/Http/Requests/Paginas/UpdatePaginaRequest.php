<?php

namespace App\Http\Requests\Paginas;

use App\Models\Pagina;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaginaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('pagina')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Pagina $pagina */
        $pagina = $this->route('pagina');

        return [
            'titulo' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(Pagina::SLUGS_RESERVADOS),
                Rule::unique('paginas', 'slug')->ignore($pagina->id),
            ],
            'conteudo' => ['nullable', 'string'],
            'ativo' => ['required', 'boolean'],
            'seo_titulo' => ['nullable', 'string', 'max:70'],
            'seo_descricao' => ['nullable', 'string', 'max:160'],
            'seo_palavras_chave' => ['nullable', 'string', 'max:255'],
            'acao' => ['nullable', 'string', Rule::in(['ver_no_site'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'titulo' => 'título',
            'slug' => 'URL',
            'conteudo' => 'conteúdo',
            'ativo' => 'ativo',
            'seo_titulo' => 'título SEO',
            'seo_descricao' => 'descrição SEO',
            'seo_palavras_chave' => 'palavras-chave SEO',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.not_in' => 'Esta URL é reservada pelo sistema.',
            'slug.regex' => 'Use apenas letras minúsculas, números e hífens.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ativo' => $this->boolean('ativo'),
            'slug' => $this->filled('slug') ? trim((string) $this->input('slug')) : null,
            'seo_titulo' => $this->filled('seo_titulo') ? trim((string) $this->input('seo_titulo')) : null,
            'seo_descricao' => $this->filled('seo_descricao') ? trim((string) $this->input('seo_descricao')) : null,
            'seo_palavras_chave' => $this->filled('seo_palavras_chave') ? trim((string) $this->input('seo_palavras_chave')) : null,
        ]);
    }
}
