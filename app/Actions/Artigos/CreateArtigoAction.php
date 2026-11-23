<?php

namespace App\Actions\Artigos;

use App\DataTransferObjects\ArtigoData;
use App\Models\Artigo;
use App\Models\User;
use App\Support\Slug\UniqueSlugGenerator;
use Illuminate\Support\Facades\DB;

class CreateArtigoAction
{
    public function __construct(
        private readonly UniqueSlugGenerator $slugGenerator,
    ) {}

    public function execute(User $criador, ArtigoData $data): Artigo
    {
        return DB::transaction(function () use ($criador, $data): Artigo {
            $artigo = Artigo::query()->create([
                'usuario_id' => $criador->id,
                'categoria_id' => $data->categoriaId,
                'titulo' => $data->titulo,
                'identificador' => $this->slugGenerator->generate(Artigo::class, $data->titulo),
                'resumo' => $data->resumo,
                'conteudo' => $data->conteudo,
                'status' => $data->status,
                'publicado_em' => $data->publicadoEm,
            ]);

            $artigo->autores()->sync($data->autorIds);

            if ($data->palavraChaveIds !== []) {
                $artigo->palavrasChave()->sync($data->palavraChaveIds);
            }

            return $artigo->load([
                'criador:id,nome,nickname',
                'autores:id,nome,nickname',
                'categoria:id,nome',
                'palavrasChave:id,nome',
            ]);
        });
    }
}
