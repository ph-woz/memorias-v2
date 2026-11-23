<?php

namespace App\Actions\Categorias;

use App\DataTransferObjects\CategoriaData;
use App\Models\Categoria;
use App\Support\Slug\UniqueSlugGenerator;

class UpdateCategoriaAction
{
    public function __construct(
        private readonly UniqueSlugGenerator $slugGenerator,
        private readonly StoreCategoriaFotoAction $storeCategoriaFotoAction,
    ) {}

    public function execute(Categoria $categoria, CategoriaData $data): Categoria
    {
        $identificador = $categoria->identificador === $data->identificador
            && $categoria->nome === $data->nome
            ? $categoria->identificador
            : $this->slugGenerator->generate(
                Categoria::class,
                $data->identificador,
                $categoria->id,
            );

        $atributos = [
            'nome' => $data->nome,
            'identificador' => $identificador,
            'seo_titulo' => $data->seoTitulo,
            'seo_descricao' => $data->seoDescricao,
            'seo_palavras_chave' => $data->seoPalavrasChave,
        ];

        if ($data->fotoArquivo !== null) {
            $atributos['foto'] = $this->storeCategoriaFotoAction->execute(
                $data->fotoArquivo,
                $categoria->foto,
            );
        }

        $categoria->update($atributos);

        return $categoria->refresh();
    }
}
