<?php

namespace Database\Factories;

use App\Enums\PosicaoHorizontal;
use App\Enums\PosicaoVertical;
use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition(): array
    {
        Storage::fake('public');

        $arquivo = UploadedFile::fake()->image('banner.jpg', 1600, 700);
        $caminho = $arquivo->store('banners', 'public');

        return [
            'titulo' => fake()->sentence(4),
            'slogan' => fake()->optional()->sentence(8),
            'imagem' => $caminho,
            'overlay_cor' => '#000000',
            'overlay_opacidade' => 45,
            'posicao_horizontal' => PosicaoHorizontal::Esquerda,
            'posicao_vertical' => PosicaoVertical::Baixo,
            'cta_texto' => 'Saiba mais',
            'cta_link' => 'https://example.com',
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

    public function semOverlay(): static
    {
        return $this->state(fn (): array => [
            'overlay_opacidade' => 0,
        ]);
    }
}
