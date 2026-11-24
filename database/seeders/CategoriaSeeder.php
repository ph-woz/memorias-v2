<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            [
                'nome' => 'Memórias',
                'foto' => 'https://images.unsplash.com/photo-1511895426328-dc8714191300?auto=format&fit=crop&w=1200&q=80',
                'seo_titulo' => 'Memórias de família para guardar e compartilhar',
                'seo_descricao' => 'Registros afetivos, fotos e relatos que atravessam gerações.',
                'seo_palavras_chave' => 'memórias, família, álbum, histórias',
            ],
            [
                'nome' => 'Histórias',
                'foto' => 'https://images.unsplash.com/photo-1452587925148-ce544e77e70d?auto=format&fit=crop&w=1200&q=80',
                'seo_titulo' => 'Histórias reais contadas com carinho',
                'seo_descricao' => 'Narrativas pessoais, depoimentos e episódios marcantes da vida.',
                'seo_palavras_chave' => 'histórias, depoimentos, narrativas, vida',
            ],
            [
                'nome' => 'Notícias',
                'foto' => 'https://images.unsplash.com/photo-1504711434719-9466b4b8b3c2?auto=format&fit=crop&w=1200&q=80',
                'seo_titulo' => 'Notícias e novidades do universo das memórias',
                'seo_descricao' => 'Atualizações, dicas e lançamentos para quem preserva o passado.',
                'seo_palavras_chave' => 'notícias, novidades, dicas, memórias',
            ],
            [
                'nome' => 'Viagens',
                'foto' => 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&w=1200&q=80',
                'seo_titulo' => 'Viagens e roteiros que viraram memória',
                'seo_descricao' => 'Diários de bordo, destinos e momentos inesquecíveis na estrada.',
                'seo_palavras_chave' => 'viagens, roteiros, turismo, diário',
            ],
            [
                'nome' => 'Culinária',
                'foto' => 'https://images.unsplash.com/photo-1495521821757-a1efb6729352?auto=format&fit=crop&w=1200&q=80',
                'seo_titulo' => 'Receitas e sabores de família',
                'seo_descricao' => 'Pratos tradicionais, cadernos de receitas e cheiros da cozinha.',
                'seo_palavras_chave' => 'culinária, receitas, família, tradição',
            ],
        ];

        foreach ($categorias as $categoria) {
            Categoria::query()->updateOrCreate(
                ['slug' => Str::slug($categoria['nome'])],
                $categoria,
            );
        }
    }
}
