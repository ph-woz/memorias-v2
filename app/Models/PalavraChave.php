<?php

namespace App\Models;

use Database\Factories\PalavraChaveFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PalavraChave extends Model
{
    /** @use HasFactory<PalavraChaveFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'palavras_chave';

    protected $fillable = [
        'nome',
    ];

    public function artigos(): BelongsToMany
    {
        return $this->belongsToMany(Artigo::class, 'artigo_palavra_chave', 'palavra_chave_id', 'artigo_id');
    }

    public static function encontrarOuCriarPorNome(string $nome): self
    {
        $existente = static::withTrashed()
            ->whereRaw('LOWER(nome) = ?', [Str::lower($nome)])
            ->first();

        if ($existente !== null) {
            if ($existente->trashed()) {
                $existente->restore();
            }

            return $existente;
        }

        return static::query()->create(['nome' => $nome]);
    }
}
