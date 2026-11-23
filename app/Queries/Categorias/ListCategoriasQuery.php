<?php

namespace App\Queries\Categorias;

use App\Models\Categoria;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListCategoriasQuery
{
    private const PER_PAGE = 15;

    public function paginate(?string $busca = null): LengthAwarePaginator
    {
        return Categoria::query()
            ->select([
                'id',
                'nome',
                'identificador',
                'foto',
                'seo_titulo',
                'criado_em',
                'atualizado_em',
            ])
            ->withCount('artigos')
            ->when($busca, function ($query, string $termo): void {
                $query->where(function ($inner) use ($termo): void {
                    $inner
                        ->where('nome', 'like', '%'.$termo.'%')
                        ->orWhere('identificador', 'like', '%'.$termo.'%');
                });
            })
            ->orderBy('nome')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }
}
