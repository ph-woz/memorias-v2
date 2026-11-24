<?php

namespace App\Models;

use App\Support\Slug\UniqueSlugGenerator;
use Database\Factories\AreaTematicaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AreaTematica extends Model
{
    /** @use HasFactory<AreaTematicaFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'areas_tematicas';

    protected $fillable = [
        'nome',
        'slug',
    ];

    public function artigos(): BelongsToMany
    {
        return $this->belongsToMany(Artigo::class, 'artigo_area_tematica', 'area_tematica_id', 'artigo_id');
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

        return static::query()->create([
            'nome' => $nome,
            'slug' => app(UniqueSlugGenerator::class)->generate(static::class, $nome),
        ]);
    }
}
