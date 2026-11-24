<?php

namespace Database\Factories;

use App\Enums\StatusArtigo;
use App\Models\Artigo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Artigo>
 */
class ArtigoFactory extends Factory
{
    protected $model = Artigo::class;

    public function definition(): array
    {
        $titulo = fake()->sentence(6);

        return [
            'cadastrante_id' => User::factory(),
            'titulo' => $titulo,
            'slug' => Str::slug($titulo).'-'.fake()->unique()->numerify('###'),
            'resumo' => fake()->optional()->sentence(12),
            'conteudo' => fake()->paragraphs(3, true),
            'status' => StatusArtigo::Rascunho,
            'publicado_em' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Artigo $artigo): void {
            if ($artigo->autores()->exists()) {
                return;
            }

            $artigo->autores()->create([
                'nome' => $artigo->cadastrante?->nome ?? fake('pt_BR')->name(),
                'ordem' => 0,
            ]);
        });
    }

    public function publicado(): static
    {
        return $this->state(fn (): array => [
            'status' => StatusArtigo::Publicado,
            'publicado_em' => now()->subHour(),
        ]);
    }
}
