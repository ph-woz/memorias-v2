<?php

namespace Database\Factories;

use App\Models\Pagina;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Pagina>
 */
class PaginaFactory extends Factory
{
    protected $model = Pagina::class;

    public function definition(): array
    {
        $titulo = fake()->unique()->sentence(3);

        return [
            'titulo' => rtrim($titulo, '.'),
            'slug' => Str::slug($titulo),
            'conteudo' => '<p>'.fake()->paragraphs(2, true).'</p>',
            'ativo' => true,
            'seo_titulo' => null,
            'seo_descricao' => fake()->optional()->sentence(),
            'seo_palavras_chave' => null,
        ];
    }

    public function inativa(): static
    {
        return $this->state(fn (): array => [
            'ativo' => false,
        ]);
    }
}
