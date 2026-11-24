<?php

namespace App\Models;

use App\Support\Slug\UniqueSlugGenerator;
use Database\Factories\EtiquetaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Etiqueta extends Model
{
    /** @use HasFactory<EtiquetaFactory> */
    use HasFactory;

    use SoftDeletes;

    public const COR_PADRAO = '#6366f1';

    protected $table = 'etiquetas';

    protected $fillable = [
        'nome',
        'slug',
        'cor',
    ];

    public function artigos(): BelongsToMany
    {
        return $this->belongsToMany(Artigo::class, 'artigo_etiqueta', 'etiqueta_id', 'artigo_id');
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
            'cor' => self::COR_PADRAO,
        ]);
    }
}
