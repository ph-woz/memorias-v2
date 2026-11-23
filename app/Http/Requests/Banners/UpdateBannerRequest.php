<?php

namespace App\Http\Requests\Banners;

use App\Enums\PosicaoHorizontal;
use App\Enums\PosicaoVertical;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('banner')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'slogan' => ['nullable', 'string', 'max:255'],
            'imagem' => ['nullable', 'image', 'max:5120'],
            'overlay_cor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'overlay_opacidade' => ['required', 'integer', 'min:0', 'max:100'],
            'posicao_horizontal' => ['required', 'string', Rule::enum(PosicaoHorizontal::class)],
            'posicao_vertical' => ['required', 'string', Rule::enum(PosicaoVertical::class)],
            'cta_texto' => ['nullable', 'string', 'max:80'],
            'cta_link' => ['nullable', 'required_with:cta_texto', 'string', 'max:2048'],
            'ativo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'titulo' => 'título',
            'slogan' => 'slogan',
            'imagem' => 'imagem',
            'overlay_cor' => 'cor do overlay',
            'overlay_opacidade' => 'opacidade do overlay',
            'posicao_horizontal' => 'posição horizontal',
            'posicao_vertical' => 'posição vertical',
            'cta_texto' => 'texto do CTA',
            'cta_link' => 'link do CTA',
            'ativo' => 'ativo',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ativo' => $this->boolean('ativo'),
            'overlay_opacidade' => (int) $this->input('overlay_opacidade', 45),
        ]);
    }
}
