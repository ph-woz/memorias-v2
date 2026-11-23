<?php

namespace App\Queries\Banners;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Collection;

class ListBannersQuery
{
    /**
     * @return Collection<int, Banner>
     */
    public function all(?string $busca = null, string $status = 'todos'): Collection
    {
        return Banner::query()
            ->select([
                'id',
                'titulo',
                'slogan',
                'imagem',
                'overlay_cor',
                'overlay_opacidade',
                'posicao_horizontal',
                'posicao_vertical',
                'cta_texto',
                'cta_link',
                'ordem',
                'ativo',
                'atualizado_em',
            ])
            ->when($busca, function ($query, string $termo): void {
                $query->where(function ($inner) use ($termo): void {
                    $inner
                        ->where('titulo', 'like', '%'.$termo.'%')
                        ->orWhere('slogan', 'like', '%'.$termo.'%');
                });
            })
            ->when($status === 'ativos', fn ($query) => $query->where('ativo', true))
            ->when($status === 'desativados', fn ($query) => $query->where('ativo', false))
            ->ordenados()
            ->get();
    }

    /**
     * @return Collection<int, Banner>
     */
    public function ativosParaHome(): Collection
    {
        return Banner::query()
            ->ativos()
            ->ordenados()
            ->get([
                'id',
                'titulo',
                'slogan',
                'imagem',
                'overlay_cor',
                'overlay_opacidade',
                'posicao_horizontal',
                'posicao_vertical',
                'cta_texto',
                'cta_link',
                'ordem',
            ]);
    }
}
