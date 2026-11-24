<?php

namespace App\Models;

use Database\Factories\PaginaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pagina extends Model
{
    /** @use HasFactory<PaginaFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * Slugs reservados para rotas do sistema (segmento raiz).
     *
     * @var list<string>
     */
    public const SLUGS_RESERVADOS = [
        'admin',
        'article',
        'busca',
        'categoria',
        'area',
        'login',
        'register',
        'forgot-password',
        'reset-password',
        'up',
        'storage',
        'horizon',
        'livewire',
        'build',
        'favicon.ico',
        'apple-touch-icon.png',
    ];

    protected $table = 'paginas';

    protected $fillable = [
        'titulo',
        'slug',
        'conteudo',
        'ativo',
        'seo_titulo',
        'seo_descricao',
        'seo_palavras_chave',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function urlPublica(): string
    {
        return url('/'.$this->slug);
    }
}
