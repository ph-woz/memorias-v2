<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Pagina;
use Illuminate\View\View;

class PaginaController extends Controller
{
    public function __invoke(Pagina $pagina): View
    {
        abort_unless($pagina->ativo, 404);

        return view('site.paginas.show', [
            'pagina' => $pagina,
        ]);
    }
}
