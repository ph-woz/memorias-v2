<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Artigo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BuscaController extends Controller
{
    public function __invoke(Request $request): View
    {
        $dados = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $termo = trim((string) ($dados['q'] ?? ''));
        $buscou = $termo !== '';

        $artigos = Artigo::query()
            ->publicados()
            ->when(
                $buscou,
                static fn ($query) => $query->buscar($termo),
                static fn ($query) => $query->whereRaw('0 = 1'),
            )
            ->with([
                'autores:id,artigo_id,nome,ordem',
                'categorias:id,nome,slug',
            ])
            ->latest('publicado_em')
            ->paginate(12)
            ->withQueryString();

        return view('site.busca.index', [
            'termo' => $termo,
            'artigos' => $artigos,
            'buscou' => $buscou,
        ]);
    }
}
