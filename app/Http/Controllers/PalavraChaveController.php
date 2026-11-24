<?php

namespace App\Http\Controllers;

use App\Http\Requests\PalavrasChave\StorePalavraChaveRequest;
use App\Http\Requests\PalavrasChave\UpdatePalavraChaveRequest;
use App\Models\PalavraChave;
use App\Support\SoftDeleteListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PalavraChaveController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', PalavraChave::class);

        $busca = $request->string('q')->toString();
        $status = $request->string('status', 'todos')->toString();

        if (! in_array($status, ['excluidos', 'todos'], true)) {
            $status = 'todos';
        }

        $excluidos = $status === 'excluidos';

        $query = PalavraChave::query()
            ->select([
                'id',
                'nome',
                'created_at',
                'updated_at',
                'deleted_at',
            ])
            ->withCount('artigos')
            ->when(
                $busca !== '',
                fn ($query) => $query->where('nome', 'like', '%'.$busca.'%'),
            )
            ->orderBy('nome');

        $palavrasChave = SoftDeleteListing::apply($query, $excluidos)
            ->paginate(15)
            ->withQueryString();

        return view('palavras-chave.index', [
            'palavrasChave' => $palavrasChave,
            'busca' => $busca,
            'status' => $status,
            'excluidos' => $excluidos,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', PalavraChave::class);

        return view('palavras-chave.create');
    }

    public function store(StorePalavraChaveRequest $request): RedirectResponse
    {
        $this->authorize('create', PalavraChave::class);

        $palavraChave = PalavraChave::query()->create($request->validated());

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
    ): RedirectResponse {
        $palavraChave->update($request->validated());

        return redirect()
            ->route('palavras_chave.edit', $palavraChave)
            ->with('status', 'palavra-chave-atualizada');
    }

    public function destroy(PalavraChave $palavraChave): RedirectResponse
    {
        $this->authorize('delete', $palavraChave);

        $palavraChave->delete();

        return redirect()
            ->route('palavras_chave.index')
            ->with('status', 'palavra-chave-excluida');
    }

    public function restore(PalavraChave $palavraChave): RedirectResponse
    {
        $this->authorize('restore', $palavraChave);

        $palavraChave->restore();

        return redirect()
            ->route('palavras_chave.index', ['status' => 'excluidos'])
            ->with('status', 'palavra-chave-restaurada');
    }
}
