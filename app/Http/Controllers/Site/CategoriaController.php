<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    public function __invoke(Categoria $categoria): View
    {
        $artigos = $categoria->artigos()
            ->publicados()
            ->with([
                'autores:id,artigo_id,nome,ordem',
                'categorias:id,nome,slug',
            ])
            ->latest('publicado_em')
            ->paginate(12);

        return view('site.categorias.show', [
            'categoria' => $categoria,
            'artigos' => $artigos,
        ]);
    }
}
