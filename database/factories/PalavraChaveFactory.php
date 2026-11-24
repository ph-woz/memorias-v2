<?php

namespace Database\Factories;

use App\Models\PalavraChave;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PalavraChave>
 */
class PalavraChaveFactory extends Factory
{
    protected $model = PalavraChave::class;

    public function definition(): array
    {
        return [
            'nome' => Str::title(fake()->unique()->word()),
        ];
    }
}
