<?php

namespace App\Http\Controllers;

use App\Models\AreaTematica;
use App\Models\Artigo;
use App\Models\Banner;
use App\Models\Categoria;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $banners = Banner::query()
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

        $artigos = Artigo::query()
            ->publicados()
            ->with([
                'categorias:id,nome,slug',
                'autores:id,artigo_id,nome,ordem',
            ])
            ->latest('publicado_em')
            ->limit(7)
            ->get([
                'id',
                'titulo',
                'slug',
                'foto',
                'resumo',
                'publicado_em',
            ]);

        $categorias = Categoria::query()
            ->withCount(['artigos as total_publicados' => fn ($query) => $query->publicados()])
            ->orderBy('nome')
            ->get(['id', 'nome', 'slug', 'foto']);

        $areasTematicas = AreaTematica::query()
            ->withCount(['artigos as total_publicados' => fn ($query) => $query->publicados()])
            ->having('total_publicados', '>', 0)
            ->orderByDesc('total_publicados')
            ->orderBy('nome')
            ->limit(10)
            ->get(['id', 'nome', 'slug']);

        return view('home', [
            'banners' => $banners,
            'destaque' => $artigos->first(),
            'artigos' => $artigos->slice(1)->values(),
            'categorias' => $categorias,
            'areasTematicas' => $areasTematicas,
        ]);
    }
}
