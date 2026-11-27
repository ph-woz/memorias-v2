<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Artigo;
use Illuminate\View\View;

class ArtigoController extends Controller
{
    public function __invoke(Artigo $artigo): View
    {
        $artigo->load([
            'autores:id,artigo_id,nome,marcadores_afiliacao,ordem',
            'referencias:id,artigo_id,texto,ordem',
            'documentos:id,artigo_id,titulo,arquivo,url,ordem',
            'categorias:id,nome,slug',
            'palavrasChave:id,nome',
            'areasTematicas:id,nome,slug',
            'etiquetas:id,nome,slug,cor',
        ]);

        $categoriaIds = $artigo->categorias->modelKeys();

        $relacionados = Artigo::query()
            ->publicados()
            ->whereKeyNot($artigo->id)
            ->when(
                $categoriaIds !== [],
                fn ($query) => $query->whereHas(
                    'categorias',
                    fn ($categorias) => $categorias->whereIn('categorias.id', $categoriaIds),
                ),
            )
            ->with([
                'categorias:id,nome,slug',
                'autores:id,artigo_id,nome,marcadores_afiliacao,ordem',
            ])
            ->latest('publicado_em')
            ->limit(3)
            ->get([
                'id',
                'titulo',
                'slug',
                'foto',
                'resumo',
                'publicado_em',
            ]);

        return view('site.artigos.show', [
            'artigo' => $artigo,
            'relacionados' => $relacionados,
        ]);
    }
}
