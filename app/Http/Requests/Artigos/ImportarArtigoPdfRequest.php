<?php

namespace App\Http\Requests\Artigos;

use App\Models\Artigo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportarArtigoPdfRequest extends FormRequest
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
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'pdf' => 'PDF',
        ];
    }
}
