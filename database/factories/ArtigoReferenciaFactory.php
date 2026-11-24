<?php

namespace Database\Factories;

use App\Models\Artigo;
use App\Models\ArtigoReferencia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArtigoReferencia>
 */
class ArtigoReferenciaFactory extends Factory
{
    protected $model = ArtigoReferencia::class;

    public function definition(): array
    {
        return [
            'artigo_id' => Artigo::factory(),
            'texto' => fake('pt_BR')->sentence(12),
            'ordem' => 0,
        ];
    }
}
