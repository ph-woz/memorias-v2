<?php

namespace App\Http\Requests\Menu;

use App\Http\Requests\Menu\Concerns\ValidaMenuItem;
use App\Models\MenuItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMenuItemRequest extends FormRequest
{
    use ValidaMenuItem;

    public function authorize(): bool
    {
        return $this->user()?->can('create', MenuItem::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->regrasMenuItem();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->atributosMenuItem();
    }

    public function withValidator(Validator $validator): void
    {
        $this->validarRegrasDeNegocioMenuItem($validator);
    }

    protected function prepareForValidation(): void
    {
        $this->prepararBooleansMenuItem();
    }
}
