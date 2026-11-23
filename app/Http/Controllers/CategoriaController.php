<?php

namespace App\Http\Controllers;

use App\Actions\Categorias\CreateCategoriaAction;
use App\Actions\Categorias\DeleteCategoriaAction;
use App\Actions\Categorias\UpdateCategoriaAction;
use App\DataTransferObjects\CategoriaData;
use App\Http\Requests\Categorias\StoreCategoriaRequest;
use App\Http\Requests\Categorias\UpdateCategoriaRequest;
use App\Models\Categoria;
use App\Queries\Categorias\ListCategoriasQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    public function index(Request $request, ListCategoriasQuery $query): View
    {
        $this->authorize('viewAny', Categoria::class);

        $categorias = $query->paginate($request->string('q')->toString() ?: null);

        return view('categorias.index', [
            'categorias' => $categorias,
            'busca' => $request->string('q')->toString(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Categoria::class);

        return view('categorias.create');
    }

    public function store(StoreCategoriaRequest $request, CreateCategoriaAction $action): RedirectResponse
    {
        $this->authorize('create', Categoria::class);

        $categoria = $action->execute(CategoriaData::fromArray($request->validated()));

        return redirect()
            ->route('categorias.edit', $categoria)
            ->with('status', 'categoria-criada');
    }

    public function edit(Categoria $categoria): View
    {
        $this->authorize('update', $categoria);

        return view('categorias.edit', ['categoria' => $categoria]);
    }

    public function update(
        UpdateCategoriaRequest $request,
        Categoria $categoria,
        UpdateCategoriaAction $action,
    ): RedirectResponse {
        $action->execute($categoria, CategoriaData::fromArray($request->validated()));

        return redirect()
            ->route('categorias.edit', $categoria)
            ->with('status', 'categoria-atualizada');
    }

    public function destroy(Categoria $categoria, DeleteCategoriaAction $action): RedirectResponse
    {
        $this->authorize('delete', $categoria);

        $action->execute($categoria);

        return redirect()
            ->route('categorias.index')
            ->with('status', 'categoria-excluida');
    }
}
