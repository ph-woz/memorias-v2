<?php

namespace Database\Factories;

use App\Models\AreaTematica;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AreaTematica>
 */
class AreaTematicaFactory extends Factory
{
    protected $model = AreaTematica::class;

    public function definition(): array
    {
        $nome = fake()->unique()->words(2, true);

        return [
            'nome' => Str::title($nome),
            'slug' => Str::slug($nome),
        ];
    }
}
