<?php

namespace App\Models;

use Database\Factories\CategoriaFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Categoria extends Model
{
    /** @use HasFactory<CategoriaFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'categorias';

    protected $fillable = [
        'nome',
        'slug',
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

    public function artigos(): BelongsToMany
    {
        return $this->belongsToMany(Artigo::class, 'artigo_categoria', 'categoria_id', 'artigo_id');
    }
}
