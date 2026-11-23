<?php

namespace App\Models;

use App\Models\Concerns\HasPortugueseTimestamps;
use Database\Factories\CategoriaFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Categoria extends Model
{
    /** @use HasFactory<CategoriaFactory> */
    use HasFactory;

    use HasPortugueseTimestamps;

    protected $table = 'categorias';

    protected $fillable = [
        'nome',
        'identificador',
        'foto',
        'seo_titulo',
        'seo_descricao',
        'seo_palavras_chave',
    ];

    protected $appends = [
        'url_foto',
    ];

    protected function urlFoto(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->foto === null || $this->foto === '') {
                return null;
            }

            if (str_starts_with($this->foto, 'http://') || str_starts_with($this->foto, 'https://')) {
                return $this->foto;
            }

            return Storage::disk('public')->url($this->foto);
        });
    }

    public function artigos(): HasMany
    {
        return $this->hasMany(Artigo::class, 'categoria_id');
    }
}
