<?php

namespace Database\Factories;

use App\Models\Artigo;
use App\Models\ArtigoDocumento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArtigoDocumento>
 */
class ArtigoDocumentoFactory extends Factory
{
    protected $model = ArtigoDocumento::class;

    public function definition(): array
    {
        return [
            'artigo_id' => Artigo::factory(),
            'titulo' => fake('pt_BR')->sentence(3),
            'arquivo' => null,
            'url' => fake()->url(),
            'ordem' => 0,
        ];
    }

    public function comArquivo(string $caminho = 'artigos/documentos/exemplo.pdf'): static
    {
        return $this->state(fn (): array => [
            'arquivo' => $caminho,
            'url' => null,
        ]);
    }
}
