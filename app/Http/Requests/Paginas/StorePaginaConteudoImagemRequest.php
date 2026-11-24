<?php

namespace App\Http\Requests\Paginas;

use App\Models\Pagina;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePaginaConteudoImagemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Pagina::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'imagem' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'imagem' => 'imagem',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'imagem.required' => 'Selecione uma imagem para enviar.',
            'imagem.file' => 'O arquivo enviado é inválido.',
            'imagem.image' => 'O arquivo precisa ser uma imagem válida (JPEG, PNG, WebP ou GIF).',
            'imagem.mimes' => 'Use uma imagem JPEG, PNG, WebP ou GIF.',
            'imagem.max' => 'A imagem pode ter no máximo 5 MB.',
            'imagem.uploaded' => 'Falha ao enviar a imagem. Verifique o tamanho (máx. 5 MB) e tente novamente.',
        ];
    }
}
