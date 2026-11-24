<?php

namespace App\Models;

use Database\Factories\ArtigoAutorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtigoAutor extends Model
{
    /** @use HasFactory<ArtigoAutorFactory> */
    use HasFactory;

    protected $table = 'artigo_autores';

    protected $fillable = [
        'artigo_id',
        'nome',
        'ordem',
    ];

    protected function casts(): array
    {
        return [
            'ordem' => 'integer',
        ];
    }

    public function artigo(): BelongsTo
    {
        return $this->belongsTo(Artigo::class);
    }
}
