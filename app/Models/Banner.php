<?php

namespace App\Models;

use App\Enums\PosicaoHorizontal;
use App\Enums\PosicaoVertical;
use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    /** @use HasFactory<BannerFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'banners';

    protected $fillable = [
        'titulo',
        'slogan',
        'imagem',
        'overlay_cor',
        'overlay_opacidade',
        'posicao_horizontal',
        'posicao_vertical',
        'cta_texto',
        'cta_link',
        'ordem',
        'ativo',
    ];

    protected $appends = [
        'url_imagem',
    ];

    protected function casts(): array
    {
        return [
            'overlay_opacidade' => 'integer',
            'posicao_horizontal' => PosicaoHorizontal::class,
            'posicao_vertical' => PosicaoVertical::class,
            'ordem' => 'integer',
            'ativo' => 'boolean',
        ];
    }

    protected function urlImagem(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->imagem === null || $this->imagem === '') {
                return null;
            }

            if (str_starts_with($this->imagem, 'http://') || str_starts_with($this->imagem, 'https://')) {
                return $this->imagem;
            }

            return Storage::disk('public')->url($this->imagem);
        });
    }

    public function temCta(): bool
    {
        return filled($this->cta_texto) && filled($this->cta_link);
    }

    public function temOverlay(): bool
    {
        return $this->overlay_opacidade > 0;
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeOrdenados(Builder $query): Builder
    {
        return $query->orderBy('ordem')->orderBy('id');
    }
}
