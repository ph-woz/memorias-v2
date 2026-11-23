<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\StatusUsuario;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['nome', 'nickname', 'email', 'password', 'email_verified_at', 'status', 'ultimo_acesso_em'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => StatusUsuario::class,
            'ultimo_acesso_em' => 'datetime',
        ];
    }

    public function estaAtivo(): bool
    {
        return $this->status->estaAtivo();
    }

    public function registrarAcesso(): void
    {
        $this->forceFill([
            'ultimo_acesso_em' => now(),
        ])->save();
    }

    public function artigosComoAutor(): BelongsToMany
    {
        return $this->belongsToMany(Artigo::class, 'artigo_usuario', 'usuario_id', 'artigo_id');
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('status', StatusUsuario::Ativo);
    }

    public function scopeDesativados(Builder $query): Builder
    {
        return $query->where('status', StatusUsuario::Desativado);
    }
}
