<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\AreaTematica;
use Illuminate\View\View;

class AreaTematicaController extends Controller
{
    public function __invoke(AreaTematica $areaTematica): View
    {
        $artigos = $areaTematica->artigos()
            ->publicados()
            ->with([
                'autores:id,artigo_id,nome,ordem',
                'categorias:id,nome,slug',
            ])
            ->latest('publicado_em')
            ->paginate(12);

        return view('site.areas-tematicas.show', [
            'areaTematica' => $areaTematica,
            'artigos' => $artigos,
        ]);
    }
}
