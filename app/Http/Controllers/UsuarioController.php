<?php

namespace App\Http\Controllers;

use App\Enums\StatusUsuario;
use App\Http\Requests\Usuarios\StoreUsuarioRequest;
use App\Http\Requests\Usuarios\UpdateUsuarioRequest;
use App\Models\User;
use App\Support\SoftDeleteListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $busca = $request->string('q')->toString();
        $status = $request->string('status', 'todos')->toString();

        if (! in_array($status, ['excluidos', 'todos', 'ativos', 'desativados'], true)) {
            $status = 'todos';
        }

        $excluidos = $status === 'excluidos';

        $query = User::query()
            ->select([
                'id',
                'nome',
                'nickname',
                'email',
                'email_verified_at',
                'status',
                'ultimo_acesso_em',
                'created_at',
                'updated_at',
                'deleted_at',
            ])
            ->when(
                $busca !== '',
                fn ($query) => $query->whereAny(['nome', 'nickname', 'email'], 'like', '%'.$busca.'%'),
            )
            ->when($status === 'ativos', fn ($query) => $query->where('status', StatusUsuario::Ativo))
            ->when($status === 'desativados', fn ($query) => $query->where('status', StatusUsuario::Desativado))
            ->orderBy('nome');

        $usuarios = SoftDeleteListing::apply($query, $excluidos)
            ->paginate(15)
            ->withQueryString();

        return view('usuarios.index', [
            'usuarios' => $usuarios,
            'busca' => $busca,
            'status' => $status,
            'excluidos' => $excluidos,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('usuarios.create', [
            'statuses' => StatusUsuario::cases(),
        ]);
    }

    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $dados = $request->safe()->only(['nome', 'nickname', 'email', 'password', 'status']);

        $usuario = User::query()->create($dados);

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

    public function update(UpdateUsuarioRequest $request, User $usuario): RedirectResponse
    {
        $dados = $request->safe()->only(['nome', 'nickname', 'email', 'status']);

        if (filled($request->validated('password'))) {
            $dados['password'] = $request->validated('password');
        }

        $usuario->update($dados);

        return redirect()
            ->route('usuarios.edit', $usuario)
            ->with('status', 'usuario-atualizado');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        $this->authorize('delete', $usuario);

        if ($request->user()?->id === $usuario->id) {
            throw new RuntimeException('Você não pode excluir a própria conta por aqui.');
        }

        $usuario->delete();

        return redirect()
            ->route('usuarios.index')
            ->with('status', 'usuario-excluido');
    }

    public function restore(User $usuario): RedirectResponse
    {
        $this->authorize('restore', $usuario);

        $usuario->restore();

        return redirect()
            ->route('usuarios.index', ['status' => 'excluidos'])
            ->with('status', 'usuario-restaurado');
    }
}
