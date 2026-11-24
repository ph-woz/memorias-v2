<?php

namespace App\Http\Requests\Menu;

use App\Models\MenuItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReorderMenuItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reorder', MenuItem::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ordem' => ['required', 'array', 'min:1'],
            'ordem.*' => ['required', 'integer', 'distinct', 'exists:menu_itens,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'ordem' => 'ordem',
            'ordem.*' => 'item do menu',
        ];
    }
}
