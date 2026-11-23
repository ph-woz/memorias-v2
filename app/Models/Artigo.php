<?php

namespace App\Models;

use App\Enums\StatusArtigo;
use App\Models\Concerns\HasPortugueseSoftDeletes;
use App\Models\Concerns\HasPortugueseTimestamps;
use Database\Factories\ArtigoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Artigo extends Model
{
    /** @use HasFactory<ArtigoFactory> */
    use HasFactory;
    use HasPortugueseSoftDeletes;
    use HasPortugueseTimestamps;

    protected $table = 'artigos';

    protected $fillable = [
        'usuario_id',
        'categoria_id',
        'titulo',
        'identificador',
        'resumo',
        'conteudo',
        'status',
        'publicado_em',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusArtigo::class,
            'publicado_em' => 'datetime',
        ];
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function autores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'artigo_usuario', 'artigo_id', 'usuario_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function palavrasChave(): BelongsToMany
    {
        return $this->belongsToMany(PalavraChave::class, 'artigo_palavra_chave', 'artigo_id', 'palavra_chave_id');
    }

    public function scopePublicados(Builder $query): Builder
    {
        return $query
            ->where('status', StatusArtigo::Publicado)
            ->whereNotNull('publicado_em')
            ->where('publicado_em', '<=', now());
    }

    public function estaPublicado(): bool
    {
        return $this->status === StatusArtigo::Publicado;
    }

    public function temAutor(User $usuario): bool
    {
        if ($this->relationLoaded('autores')) {
            return $this->autores->contains('id', $usuario->id);
        }

        return $this->autores()->whereKey($usuario->id)->exists();
    }
}
