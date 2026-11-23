<?php

namespace App\Queries\Usuarios;

use App\Enums\StatusUsuario;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListUsuariosQuery
{
    private const PER_PAGE = 15;

    public function paginate(?string $busca = null, string $status = 'todos'): LengthAwarePaginator
    {
        return User::query()
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
            ])
            ->when($busca, function ($query, string $termo): void {
                $query->where(function ($inner) use ($termo): void {
                    $inner
                        ->where('nome', 'like', '%'.$termo.'%')
                        ->orWhere('nickname', 'like', '%'.$termo.'%')
                        ->orWhere('email', 'like', '%'.$termo.'%');
                });
            })
            ->when($status === 'ativos', fn ($query) => $query->where('status', StatusUsuario::Ativo))
            ->when($status === 'desativados', fn ($query) => $query->where('status', StatusUsuario::Desativado))
            ->orderBy('nome')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }
}
