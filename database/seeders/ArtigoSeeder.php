<?php

namespace Database\Seeders;

use App\Enums\StatusArtigo;
use App\Models\AreaTematica;
use App\Models\Artigo;
use App\Models\Categoria;
use App\Models\Etiqueta;
use App\Models\PalavraChave;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArtigoSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = User::query()->get();
        $categorias = Categoria::query()->get();
        $palavrasChave = PalavraChave::query()->get();
        $etiquetas = Etiqueta::query()->get();
        $areasTematicas = AreaTematica::query()->get();

        if ($usuarios->isEmpty()) {
            return;
        }

        $titulos = [
            'O domingo em que a família inteira se reuniu na varanda',
            'Cartas da vovó que reencontramos no fundo do armário',
            'A receita de bolo que atravessou três gerações',
            'Viagem de trem para o interior e as histórias do caminho',
            'Primeiro dia de aula e o cheiro de caderno novo',
            'Fotografia em preto e branco do casamento dos avós',
            'Quando o rádio tocava e a casa parava para ouvir',
            'O quintal onde plantamos a árvore do aniversário',
            'Diário de bordo da mudança para a cidade grande',
            'As histórias que o tio contava depois do jantar',
            'Natal de 1987 e a mesa cheia de gente',
            'O álbum esquecido na caixa de sapatos',
            'Primeiro emprego e a carta de apresentação manuscrita',
            'Férias na praia com os primos e o farol ao fundo',
            'A escola do bairro e os recreios intermináveis',
            'Herança de objetos simples e memórias enormes',
            'O casamento no salão da igreja da esquina',
            'Quando aprendemos a cozinhar com a mãe',
            'Postais enviados da Europa nos anos 70',
            'A despedida do avô e as palavras que ficaram',
            'Bairro antigo, calçadas estreitas e amigos de infância',
            'O piano desafinado que ainda toca nas lembranças',
            'Aniversário surpresa no quintal iluminado',
            'Relatos de imigração e as malas de madeira',
            'O primeiro amor e a carta nunca enviada',
            'Feira livre de sábado e o cheiro de milho verde',
            'Livros emprestados que nunca voltaram',
            'A televisão a preto e branco e o jornal das oito',
            'Passeio de bicicleta até o rio no fim da tarde',
            'Memórias da cozinha: cheiros, panelas e conversas',
        ];

        $fotos = [
            'https://images.unsplash.com/photo-1511895426328-dc8714191300?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1452587925148-ce544e77e70d?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1504711434719-9466b4b8b3c2?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1495521821757-a1efb6729352?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=1200&q=80',
        ];

        foreach ($titulos as $index => $titulo) {
            $slug = Str::slug($titulo);
            $cadastrante = $usuarios->random();
            $publicado = $index % 4 !== 0;

            $artigo = Artigo::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'cadastrante_id' => $cadastrante->id,
                    'titulo' => $titulo,
                    'foto' => $fotos[$index % count($fotos)],
                    'resumo' => fake('pt_BR')->sentence(14),
                    'conteudo' => collect(range(1, fake()->numberBetween(3, 6)))
                        ->map(fn () => fake('pt_BR')->paragraph(5))
                        ->implode("\n\n"),
                    'status' => $publicado ? StatusArtigo::Publicado : StatusArtigo::Rascunho,
                    'publicado_em' => $publicado ? now()->subDays(fake()->numberBetween(1, 120)) : null,
                ],
            );

            $nomesAutores = collect([$cadastrante->nome])
                ->merge(
                    collect(range(1, fake()->numberBetween(0, 2)))
                        ->map(fn () => fake('pt_BR')->name()),
                )
                ->unique()
                ->values();

            foreach ($nomesAutores as $ordem => $nome) {
                $artigo->autores()->create([
                    'nome' => $nome,
                    'ordem' => $ordem,
                ]);
            }

            if ($categorias->isNotEmpty()) {
                $artigo->categorias()->sync(
                    $categorias->random(min($categorias->count(), fake()->numberBetween(1, 3)))->pluck('id')->all()
                );
            }

            if ($palavrasChave->isNotEmpty()) {
                $artigo->palavrasChave()->sync(
                    $palavrasChave->random(min($palavrasChave->count(), fake()->numberBetween(2, 5)))->pluck('id')->all()
                );
            }

            if ($etiquetas->isNotEmpty()) {
                $artigo->etiquetas()->sync(
                    $etiquetas->random(min($etiquetas->count(), fake()->numberBetween(1, 3)))->pluck('id')->all()
                );
            }

            if ($areasTematicas->isNotEmpty()) {
                $artigo->areasTematicas()->sync(
                    $areasTematicas->random(min($areasTematicas->count(), fake()->numberBetween(1, 3)))->pluck('id')->all()
                );
            }
        }
    }
}
