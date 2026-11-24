<?php

namespace App\Models;

use Database\Factories\ArtigoReferenciaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtigoReferencia extends Model
{
    /** @use HasFactory<ArtigoReferenciaFactory> */
    use HasFactory;

    protected $table = 'artigo_referencias';

    protected $fillable = [
        'artigo_id',
        'texto',
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
