<?php

namespace App\Http\Controllers;

use App\Enums\StatusArtigo;
use App\Models\Artigo;
use App\Models\Categoria;
use App\Models\Etiqueta;
use App\Support\DashboardPeriodo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $periodo = DashboardPeriodo::fromRequest($request);

        $totais = [
            'artigos' => $this->artigosAtualizadosNoPeriodo($periodo)->count(),
            'publicados' => Artigo::query()
                ->where('status', StatusArtigo::Publicado)
                ->whereNotNull('publicado_em')
                ->whereBetween('publicado_em', [$periodo->inicio, $periodo->fim])
                ->count(),
            'rascunhos' => Artigo::query()
                ->where('status', StatusArtigo::Rascunho)
                ->whereBetween('updated_at', [$periodo->inicio, $periodo->fim])
                ->count(),
            'excluidos' => Artigo::onlyTrashed()
                ->whereBetween('deleted_at', [$periodo->inicio, $periodo->fim])
                ->count(),
        ];

        $artigosRecentes = $this->artigosAtualizadosNoPeriodo($periodo)
            ->with([
                'categorias:id,nome',
                'autores:id,artigo_id,nome,ordem',
            ])
            ->latest('updated_at')
            ->limit(6)
            ->get(['id', 'titulo', 'slug', 'status', 'foto', 'updated_at', 'publicado_em']);

        $atencao = [
            'sem_foto' => $this->artigosAtualizadosNoPeriodo($periodo)
                ->where(fn (Builder $q) => $q->whereNull('foto')->orWhere('foto', ''))
                ->count(),
            'sem_categoria' => $this->artigosAtualizadosNoPeriodo($periodo)
                ->whereDoesntHave('categorias')
                ->count(),
            'rascunhos_antigos' => Artigo::query()
                ->where('status', StatusArtigo::Rascunho)
                ->whereBetween('updated_at', [$periodo->inicio, $periodo->fim])
                ->where('updated_at', '<', now()->subDays(14))
                ->count(),
        ];

        $porCategoria = Categoria::query()
            ->withCount(['artigos as total_artigos' => function (Builder $query) use ($periodo): void {
                $this->restringirArtigosAoPeriodo($query, $periodo);
            }])
            ->having('total_artigos', '>', 0)
            ->orderByDesc('total_artigos')
            ->orderBy('nome')
            ->limit(6)
            ->get(['id', 'nome', 'slug']);

        $maxCategoria = max(1, (int) $porCategoria->max('total_artigos'));

        $etiquetasPopulares = Etiqueta::query()
            ->withCount(['artigos' => function (Builder $query) use ($periodo): void {
                $this->restringirArtigosAoPeriodo($query, $periodo);
            }])
            ->having('artigos_count', '>', 0)
            ->orderByDesc('artigos_count')
            ->orderBy('nome')
            ->limit(8)
            ->get(['id', 'nome', 'slug', 'cor']);

        return view('dashboard', [
            'periodo' => $periodo,
            'totais' => $totais,
            'artigosRecentes' => $artigosRecentes,
            'atencao' => $atencao,
            'porCategoria' => $porCategoria,
            'maxCategoria' => $maxCategoria,
            'etiquetasPopulares' => $etiquetasPopulares,
        ]);
    }

    /**
     * @return Builder<Artigo>
     */
    private function artigosAtualizadosNoPeriodo(DashboardPeriodo $periodo): Builder
    {
        return Artigo::query()
            ->whereBetween('updated_at', [$periodo->inicio, $periodo->fim]);
    }

    /**
     * Artigo conta no período se foi atualizado nele ou publicado nele.
     *
     * @param  Builder<Artigo>  $query
     */
    private function restringirArtigosAoPeriodo(Builder $query, DashboardPeriodo $periodo): void
    {
        $query->where(function (Builder $periodoQuery) use ($periodo): void {
            $periodoQuery
                ->whereBetween('artigos.updated_at', [$periodo->inicio, $periodo->fim])
                ->orWhereBetween('artigos.publicado_em', [$periodo->inicio, $periodo->fim]);
        });
    }
}
