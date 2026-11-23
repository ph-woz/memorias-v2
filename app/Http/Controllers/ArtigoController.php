<?php

namespace App\Http\Controllers;

use App\Actions\Artigos\CreateArtigoAction;
use App\Actions\Artigos\DeleteArtigoAction;
use App\Actions\Artigos\UpdateArtigoAction;
use App\DataTransferObjects\ArtigoData;
use App\Enums\StatusArtigo;
use App\Http\Requests\Artigos\StoreArtigoRequest;
use App\Http\Requests\Artigos\UpdateArtigoRequest;
use App\Models\Artigo;
use App\Models\Categoria;
use App\Models\PalavraChave;
use App\Models\User;
use App\Queries\Artigos\ListArtigosQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ArtigoController extends Controller
{
    public function index(Request $request, ListArtigosQuery $query): View
    {
        $this->authorize('viewAny', Artigo::class);

        return view('artigos.index', [
            'artigos' => $query->paginate($request->string('q')->toString() ?: null),
            'busca' => $request->string('q')->toString(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Artigo::class);

        return view('artigos.create', $this->dadosFormulario());
    }

    public function store(StoreArtigoRequest $request, CreateArtigoAction $action): RedirectResponse
    {
        $this->authorize('create', Artigo::class);

        $artigo = $action->execute(
            $request->user(),
            ArtigoData::fromArray($request->validated()),
        );

        return redirect()
            ->route('artigos.edit', $artigo)
            ->with('status', 'artigo-criado');
    }

    public function edit(Artigo $artigo): View
    {
        $this->authorize('update', $artigo);

        $artigo->load(['autores:id', 'palavrasChave:id']);

        return view('artigos.edit', [
            'artigo' => $artigo,
            ...$this->dadosFormulario(),
        ]);
    }

    public function update(
        UpdateArtigoRequest $request,
        Artigo $artigo,
        UpdateArtigoAction $action,
    ): RedirectResponse {
        $action->execute(
            $artigo,
            ArtigoData::fromArray($request->validated()),
        );

        return redirect()
            ->route('artigos.edit', $artigo)
            ->with('status', 'artigo-atualizado');
    }

    public function destroy(Artigo $artigo, DeleteArtigoAction $action): RedirectResponse
    {
        $this->authorize('delete', $artigo);

        $action->execute($artigo);

        return redirect()
            ->route('artigos.index')
            ->with('status', 'artigo-excluido');
    }

    /**
     * @return array{
     *     categorias: Collection<int, Categoria>,
     *     palavrasChave: Collection<int, PalavraChave>,
     *     autores: Collection<int, User>,
     *     statuses: list<StatusArtigo>
     * }
     */
    private function dadosFormulario(): array
    {
        return [
            'categorias' => Categoria::query()->orderBy('nome')->get(['id', 'nome']),
            'palavrasChave' => PalavraChave::query()->orderBy('nome')->get(['id', 'nome']),
            'autores' => User::query()->orderBy('nome')->get(['id', 'nome', 'nickname']),
            'statuses' => StatusArtigo::cases(),
        ];
    }
}
