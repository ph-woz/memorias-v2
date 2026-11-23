<?php

namespace App\Http\Controllers;

use App\Actions\Usuarios\CreateUsuarioAction;
use App\Actions\Usuarios\DeleteUsuarioAction;
use App\Actions\Usuarios\UpdateUsuarioAction;
use App\DataTransferObjects\UsuarioData;
use App\Enums\StatusUsuario;
use App\Http\Requests\Usuarios\StoreUsuarioRequest;
use App\Http\Requests\Usuarios\UpdateUsuarioRequest;
use App\Models\User;
use App\Queries\Usuarios\ListUsuariosQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(Request $request, ListUsuariosQuery $query): View
    {
        $this->authorize('viewAny', User::class);

        $status = $request->string('status')->toString();
        if (! in_array($status, ['todos', 'ativos', 'desativados'], true)) {
            $status = 'todos';
        }

        $usuarios = $query->paginate(
            $request->string('q')->toString() ?: null,
            $status,
        );

        return view('usuarios.index', [
            'usuarios' => $usuarios,
            'busca' => $request->string('q')->toString(),
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('usuarios.create', [
            'statuses' => StatusUsuario::cases(),
        ]);
    }

    public function store(StoreUsuarioRequest $request, CreateUsuarioAction $action): RedirectResponse
    {
        $this->authorize('create', User::class);

        $usuario = $action->execute(UsuarioData::fromArray($request->validated()));

        return redirect()
            ->route('usuarios.edit', $usuario)
            ->with('status', 'usuario-criado');
    }

    public function edit(User $usuario): View
    {
        $this->authorize('update', $usuario);

        return view('usuarios.edit', [
            'usuario' => $usuario,
            'statuses' => StatusUsuario::cases(),
        ]);
    }

    public function update(
        UpdateUsuarioRequest $request,
        User $usuario,
        UpdateUsuarioAction $action,
    ): RedirectResponse {
        $action->execute($usuario, UsuarioData::fromArray($request->validated()));

        return redirect()
            ->route('usuarios.edit', $usuario)
            ->with('status', 'usuario-atualizado');
    }

    public function destroy(
        Request $request,
        User $usuario,
        DeleteUsuarioAction $action,
    ): RedirectResponse {
        $this->authorize('delete', $usuario);

        $action->execute($usuario, $request->user());

        return redirect()
            ->route('usuarios.index')
            ->with('status', 'usuario-excluido');
    }
}
