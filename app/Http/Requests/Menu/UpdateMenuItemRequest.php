<?php

namespace App\Http\Requests\Menu;

use App\Http\Requests\Menu\Concerns\ValidaMenuItem;
use App\Models\MenuItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateMenuItemRequest extends FormRequest
{
    use ValidaMenuItem;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('menuItem')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var MenuItem $menuItem */
        $menuItem = $this->route('menuItem');

        return $this->regrasMenuItem($menuItem->id);
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
        /** @var MenuItem $menuItem */
        $menuItem = $this->route('menuItem');

        $this->validarRegrasDeNegocioMenuItem($validator, $menuItem->id);
    }

    protected function prepareForValidation(): void
    {
        $this->prepararBooleansMenuItem();
    }
}
