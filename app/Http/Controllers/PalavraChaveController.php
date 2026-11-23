<?php

namespace App\Http\Controllers;

use App\Actions\PalavrasChave\CreatePalavraChaveAction;
use App\Actions\PalavrasChave\DeletePalavraChaveAction;
use App\Actions\PalavrasChave\UpdatePalavraChaveAction;
use App\DataTransferObjects\PalavraChaveData;
use App\Http\Requests\PalavrasChave\StorePalavraChaveRequest;
use App\Http\Requests\PalavrasChave\UpdatePalavraChaveRequest;
use App\Models\PalavraChave;
use App\Queries\PalavrasChave\ListPalavrasChaveQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PalavraChaveController extends Controller
{
    public function index(Request $request, ListPalavrasChaveQuery $query): View
    {
        $this->authorize('viewAny', PalavraChave::class);

        $palavrasChave = $query->paginate($request->string('q')->toString() ?: null);

        return view('palavras-chave.index', [
            'palavrasChave' => $palavrasChave,
            'busca' => $request->string('q')->toString(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', PalavraChave::class);

        return view('palavras-chave.create');
    }

    public function store(StorePalavraChaveRequest $request, CreatePalavraChaveAction $action): RedirectResponse
    {
        $this->authorize('create', PalavraChave::class);

        $palavraChave = $action->execute(PalavraChaveData::fromArray($request->validated()));

        return redirect()
            ->route('palavras_chave.edit', $palavraChave)
            ->with('status', 'palavra-chave-criada');
    }

    public function edit(PalavraChave $palavraChave): View
    {
        $this->authorize('update', $palavraChave);

        return view('palavras-chave.edit', ['palavraChave' => $palavraChave]);
    }

    public function update(
        UpdatePalavraChaveRequest $request,
        PalavraChave $palavraChave,
        UpdatePalavraChaveAction $action,
    ): RedirectResponse {
        $action->execute($palavraChave, PalavraChaveData::fromArray($request->validated()));

        return redirect()
            ->route('palavras_chave.edit', $palavraChave)
            ->with('status', 'palavra-chave-atualizada');
    }

    public function destroy(PalavraChave $palavraChave, DeletePalavraChaveAction $action): RedirectResponse
    {
        $this->authorize('delete', $palavraChave);

        $action->execute($palavraChave);

        return redirect()
            ->route('palavras_chave.index')
            ->with('status', 'palavra-chave-excluida');
    }
}
