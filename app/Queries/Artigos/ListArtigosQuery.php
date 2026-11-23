<?php

namespace App\Queries\Artigos;

use App\Models\Artigo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListArtigosQuery
{
    private const PER_PAGE = 15;

    public function paginate(?string $busca = null): LengthAwarePaginator
    {
        return Artigo::query()
            ->select([
                'id',
                'usuario_id',
                'categoria_id',
                'titulo',
                'identificador',
                'status',
                'publicado_em',
                'criado_em',
                'atualizado_em',
            ])
            ->with([
                'autores:id,nome,nickname',
                'categoria:id,nome',
            ])
            ->when($busca, function ($query, string $termo): void {
                $query->where(function ($inner) use ($termo): void {
                    $inner
                        ->where('titulo', 'like', '%'.$termo.'%')
                        ->orWhere('identificador', 'like', '%'.$termo.'%');
                });
            })
            ->latest('atualizado_em')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }
}
