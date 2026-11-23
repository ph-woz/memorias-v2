<?php

namespace App\Models;

use App\Models\Concerns\HasPortugueseTimestamps;
use Database\Factories\PalavraChaveFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PalavraChave extends Model
{
    /** @use HasFactory<PalavraChaveFactory> */
    use HasFactory;
    use HasPortugueseTimestamps;

    protected $table = 'palavras_chave';

    protected $fillable = [
        'nome',
        'identificador',
    ];

    public function artigos(): BelongsToMany
    {
        return $this->belongsToMany(Artigo::class, 'artigo_palavra_chave', 'palavra_chave_id', 'artigo_id');
    }
}
