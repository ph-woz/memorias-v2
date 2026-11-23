<?php

namespace App\Queries\PalavrasChave;

use App\Models\PalavraChave;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListPalavrasChaveQuery
{
    private const PER_PAGE = 15;

    public function paginate(?string $busca = null): LengthAwarePaginator
    {
        return PalavraChave::query()
            ->select([
                'id',
                'nome',
                'identificador',
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
