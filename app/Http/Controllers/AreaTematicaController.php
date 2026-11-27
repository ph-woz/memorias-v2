<?php

namespace App\Http\Controllers;

use App\Http\Requests\AreasTematicas\StoreAreaTematicaRequest;
use App\Http\Requests\AreasTematicas\UpdateAreaTematicaRequest;
use App\Models\AreaTematica;
use App\Support\Slug\UniqueSlugGenerator;
use App\Support\SoftDeleteListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AreaTematicaController extends Controller
{
    public function __construct(
        private readonly UniqueSlugGenerator $slugGenerator,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AreaTematica::class);

        $busca = $request->string('q')->toString();
        $status = $request->string('status', 'todos')->toString();

        if (! in_array($status, ['excluidos', 'todos'], true)) {
            $status = 'todos';
        }

        $excluidos = $status === 'excluidos';

        $query = AreaTematica::query()
            ->select([
                'id',
                'nome',
                'slug',
                'created_at',
                'updated_at',
                'deleted_at',
            ])
            ->withCount('artigos')
            ->when(
                $busca !== '',
                fn ($query) => $query->whereAny(['nome', 'slug'], 'like', '%'.$busca.'%'),
            )
            ->orderBy('nome');

        $areasTematicas = SoftDeleteListing::apply($query, $excluidos)
            ->paginate(15)
            ->withQueryString();

        return view('areas-tematicas.index', [
            'areasTematicas' => $areasTematicas,
            'busca' => $busca,
            'status' => $status,
            'excluidos' => $excluidos,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', AreaTematica::class);

        return view('areas-tematicas.create');
    }

    public function store(StoreAreaTematicaRequest $request): RedirectResponse
    {
        $this->authorize('create', AreaTematica::class);

        $dados = $request->validated();

        $areaTematica = AreaTematica::query()->create([
            'nome' => $dados['nome'],
            'slug' => $this->slugGenerator->generate(AreaTematica::class, $dados['slug']),
        ]);

        return redirect()
            ->route('areas_tematicas.edit', $areaTematica)
            ->with('status', 'area-tematica-criada');
    }

    public function edit(AreaTematica $areaTematica): View
    {
        $this->authorize('update', $areaTematica);

        return view('areas-tematicas.edit', ['areaTematica' => $areaTematica]);
    }

    public function update(
        UpdateAreaTematicaRequest $request,
        AreaTematica $areaTematica,
    ): RedirectResponse {
        $dados = $request->validated();

        $slug = $areaTematica->slug === $dados['slug'] && $areaTematica->nome === $dados['nome']
            ? $areaTematica->slug
            : $this->slugGenerator->generate(AreaTematica::class, $dados['slug'], $areaTematica->id);

        $areaTematica->update([
            'nome' => $dados['nome'],
            'slug' => $slug,
        ]);

        $areaTematica->refresh();

        $redirect = redirect()
            ->route('areas_tematicas.edit', $areaTematica)
            ->with('status', 'area-tematica-atualizada');

        if ($request->input('acao') === 'ver_no_site') {
            $redirect->with('abrir_site_url', route('site.areas_tematicas.show', $areaTematica));
        }

        return $redirect;
    }

    public function destroy(AreaTematica $areaTematica): RedirectResponse
    {
        $this->authorize('delete', $areaTematica);

        $areaTematica->delete();

        return redirect()
            ->route('areas_tematicas.index')
            ->with('status', 'area-tematica-excluida');
    }

    public function restore(AreaTematica $areaTematica): RedirectResponse
    {
        $this->authorize('restore', $areaTematica);

        $areaTematica->restore();

        return redirect()
            ->route('areas_tematicas.index', ['status' => 'excluidos'])
            ->with('status', 'area-tematica-restaurada');
    }
}
