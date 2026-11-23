<?php

namespace App\Actions\Categorias;

use App\DataTransferObjects\CategoriaData;
use App\Models\Categoria;
use App\Support\Slug\UniqueSlugGenerator;
use RuntimeException;

class CreateCategoriaAction
{
    public function __construct(
        private readonly UniqueSlugGenerator $slugGenerator,
        private readonly StoreCategoriaFotoAction $storeCategoriaFotoAction,
    ) {}

    public function execute(CategoriaData $data): Categoria
    {
        if ($data->fotoArquivo === null) {
            throw new RuntimeException('A foto da categoria é obrigatória.');
        }

        return Categoria::query()->create([
            'nome' => $data->nome,
            'identificador' => $this->slugGenerator->generate(
                Categoria::class,
                $data->identificador,
            ),
            'foto' => $this->storeCategoriaFotoAction->execute($data->fotoArquivo),
            'seo_titulo' => $data->seoTitulo,
            'seo_descricao' => $data->seoDescricao,
            'seo_palavras_chave' => $data->seoPalavrasChave,
        ]);
    }
}
