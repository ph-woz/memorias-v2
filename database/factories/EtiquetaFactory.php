<?php

namespace Database\Factories;

use App\Models\Etiqueta;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Etiqueta>
 */
class EtiquetaFactory extends Factory
{
    protected $model = Etiqueta::class;

    public function definition(): array
    {
        $nome = fake()->unique()->words(2, true);
        $cores = ['#6366F1', '#0EA5E9', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'];

        return [
            'nome' => Str::title($nome),
            'slug' => Str::slug($nome),
            'cor' => fake()->randomElement($cores),
        ];
    }
}
