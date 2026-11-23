<?php

namespace Database\Factories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Categoria>
 */
class CategoriaFactory extends Factory
{
    protected $model = Categoria::class;

    public function definition(): array
    {
        $nome = fake()->unique()->words(2, true);

        return [
            'nome' => Str::title($nome),
            'identificador' => Str::slug($nome),
            'foto' => 'https://images.unsplash.com/photo-1511895426328-dc8714191300?auto=format&fit=crop&w=800&q=80',
            'seo_titulo' => Str::limit(Str::title($nome).' | Memórias', 70, ''),
            'seo_descricao' => fake()->sentence(12),
            'seo_palavras_chave' => implode(', ', fake()->words(4)),
        ];
    }
}
