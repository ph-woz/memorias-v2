<?php

namespace App\Actions\Artigos;

use App\DataTransferObjects\ArtigoData;
use App\Models\Artigo;
use App\Support\Slug\UniqueSlugGenerator;
use Illuminate\Support\Facades\DB;

class UpdateArtigoAction
{
    public function __construct(
        private readonly UniqueSlugGenerator $slugGenerator,
    ) {}

    public function execute(Artigo $artigo, ArtigoData $data): Artigo
    {
        return DB::transaction(function () use ($artigo, $data): Artigo {
            $identificador = $artigo->titulo === $data->titulo
                ? $artigo->identificador
                : $this->slugGenerator->generate(Artigo::class, $data->titulo, $artigo->id);

            $artigo->update([
                'categoria_id' => $data->categoriaId,
                'titulo' => $data->titulo,
                'identificador' => $identificador,
                'resumo' => $data->resumo,
                'conteudo' => $data->conteudo,
                'status' => $data->status,
                'publicado_em' => $data->publicadoEm,
            ]);

            $artigo->autores()->sync($data->autorIds);
            $artigo->palavrasChave()->sync($data->palavraChaveIds);

            return $artigo->refresh()->load([
                'criador:id,nome,nickname',
                'autores:id,nome,nickname',
                'categoria:id,nome',
                'palavrasChave:id,nome',
            ]);
        });
    }
}
