<?php

namespace App\Http\Controllers;

use App\Http\Requests\Etiquetas\StoreEtiquetaRequest;
use App\Http\Requests\Etiquetas\UpdateEtiquetaRequest;
use App\Models\Etiqueta;
use App\Support\Slug\UniqueSlugGenerator;
use App\Support\SoftDeleteListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EtiquetaController extends Controller
{
    public function __construct(
        private readonly UniqueSlugGenerator $slugGenerator,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Etiqueta::class);

        $busca = $request->string('q')->toString();
        $status = $request->string('status', 'todos')->toString();

        if (! in_array($status, ['excluidos', 'todos'], true)) {
            $status = 'todos';
        }

        $excluidos = $status === 'excluidos';

        $query = Etiqueta::query()
            ->select([
                'id',
                'nome',
                'slug',
                'cor',
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

        $etiquetas = SoftDeleteListing::apply($query, $excluidos)
            ->paginate(15)
            ->withQueryString();

        return view('etiquetas.index', [
            'etiquetas' => $etiquetas,
            'busca' => $busca,
            'status' => $status,
            'excluidos' => $excluidos,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Etiqueta::class);

        return view('etiquetas.create');
    }

    public function store(StoreEtiquetaRequest $request): RedirectResponse
    {
        $this->authorize('create', Etiqueta::class);

        $dados = $request->validated();

        $etiqueta = Etiqueta::query()->create([
            'nome' => $dados['nome'],
            'slug' => $this->slugGenerator->generate(Etiqueta::class, $dados['slug']),
            'cor' => strtoupper($dados['cor']),
        ]);

        return redirect()
            ->route('etiquetas.edit', $etiqueta)
            ->with('status', 'etiqueta-criada');
    }

    public function edit(Etiqueta $etiqueta): View
    {
        $this->authorize('update', $etiqueta);

        return view('etiquetas.edit', ['etiqueta' => $etiqueta]);
    }

    public function update(UpdateEtiquetaRequest $request, Etiqueta $etiqueta): RedirectResponse
    {
        $dados = $request->validated();
        $cor = strtoupper($dados['cor']);

        $slug = $etiqueta->slug === $dados['slug'] && $etiqueta->nome === $dados['nome']
            ? $etiqueta->slug
            : $this->slugGenerator->generate(Etiqueta::class, $dados['slug'], $etiqueta->id);

        $etiqueta->update([
            'nome' => $dados['nome'],
            'slug' => $slug,
            'cor' => $cor,
        ]);

        return redirect()
            ->route('etiquetas.edit', $etiqueta)
            ->with('status', 'etiqueta-atualizada');
    }

    public function destroy(Etiqueta $etiqueta): RedirectResponse
    {
        $this->authorize('delete', $etiqueta);

        $etiqueta->delete();

        return redirect()
            ->route('etiquetas.index')
            ->with('status', 'etiqueta-excluida');
    }

    public function restore(Etiqueta $etiqueta): RedirectResponse
    {
        $this->authorize('restore', $etiqueta);

        $etiqueta->restore();

        return redirect()
            ->route('etiquetas.index', ['status' => 'excluidos'])
            ->with('status', 'etiqueta-restaurada');
    }
}
