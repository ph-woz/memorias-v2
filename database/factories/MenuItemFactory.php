<?php

namespace Database\Factories;

use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    public function definition(): array
    {
        return [
            'parent_id' => null,
            'rotulo' => fake()->words(2, true),
            'url' => '/'.fake()->slug(),
            'target_blank' => false,
            'ordem' => fake()->numberBetween(1, 20),
            'ativo' => true,
        ];
    }

    public function inativo(): static
    {
        return $this->state(fn (): array => [
            'ativo' => false,
        ]);
    }

    public function externo(): static
    {
        return $this->state(fn (): array => [
            'url' => 'https://example.com/'.fake()->slug(),
            'target_blank' => true,
        ]);
    }

    public function filhoDe(MenuItem $pai): static
    {
        return $this->state(fn (): array => [
            'parent_id' => $pai->id,
            'url' => '/'.fake()->slug(),
        ]);
    }
}
