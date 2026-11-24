<?php

namespace Database\Factories;

use App\Models\Artigo;
use App\Models\ArtigoAutor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArtigoAutor>
 */
class ArtigoAutorFactory extends Factory
{
    protected $model = ArtigoAutor::class;

    public function definition(): array
    {
        return [
            'artigo_id' => Artigo::factory(),
            'nome' => fake('pt_BR')->name(),
            'ordem' => 0,
        ];
    }
}
