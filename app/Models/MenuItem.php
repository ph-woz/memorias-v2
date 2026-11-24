<?php

namespace App\Models;

use Database\Factories\MenuItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    /** @use HasFactory<MenuItemFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'menu_itens';

    protected $fillable = [
        'parent_id',
        'rotulo',
        'url',
        'target_blank',
        'ordem',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'target_blank' => 'boolean',
            'ordem' => 'integer',
            'ativo' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function filhos(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->ordenados();
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeRaizes(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdenados(Builder $query): Builder
    {
        return $query->orderBy('ordem')->orderBy('id');
    }

    public function ehExterno(): bool
    {
        return $this->target_blank;
    }

    public function temUrl(): bool
    {
        return filled($this->url);
    }

    public function temSublinks(): bool
    {
        if ($this->relationLoaded('filhos')) {
            return $this->filhos->isNotEmpty();
        }

        return $this->filhos()->exists();
    }
}
