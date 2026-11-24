<?php

namespace App\Models;

use App\Enums\StatusArtigo;
use Database\Factories\ArtigoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Artigo extends Model
{
    /** @use HasFactory<ArtigoFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'artigos';

    protected $fillable = [
        'cadastrante_id',
        'titulo',
        'slug',
        'doi',
        'foto',
        'resumo',
        'conteudo',
        'endereco_instituicoes',
        'autor_correspondente',
        'seo_titulo',
        'seo_descricao',
        'seo_palavras_chave',
        'status',
        'publicado_em',
        'deletado_por',
    ];

    protected $appends = [
        'url_foto',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusArtigo::class,
            'publicado_em' => 'datetime',
        ];
    }

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

    public function cadastrante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cadastrante_id');
    }

    public function deletadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deletado_por');
    }

    public function autores(): HasMany
    {
        return $this->hasMany(ArtigoAutor::class)->orderBy('ordem');
    }

    public function referencias(): HasMany
    {
        return $this->hasMany(ArtigoReferencia::class)->orderBy('ordem');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(ArtigoDocumento::class)->orderBy('ordem');
    }

    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(Categoria::class, 'artigo_categoria', 'artigo_id', 'categoria_id');
    }

    public function palavrasChave(): BelongsToMany
    {
        return $this->belongsToMany(PalavraChave::class, 'artigo_palavra_chave', 'artigo_id', 'palavra_chave_id');
    }

    public function areasTematicas(): BelongsToMany
    {
        return $this->belongsToMany(AreaTematica::class, 'artigo_area_tematica', 'artigo_id', 'area_tematica_id');
    }

    public function etiquetas(): BelongsToMany
    {
        return $this->belongsToMany(Etiqueta::class, 'artigo_etiqueta', 'artigo_id', 'etiqueta_id');
    }

    public function scopePublicados(Builder $query): Builder
    {
        return $query
            ->where('status', StatusArtigo::Publicado)
            ->whereNotNull('publicado_em')
            ->where('publicado_em', '<=', now());
    }

    public function scopeBuscar(Builder $query, string $termo): Builder
    {
        $termo = trim($termo);

        if ($termo === '') {
            return $query;
        }

        $like = '%'.addcslashes($termo, '%_\\').'%';

        return $query->where(function (Builder $busca) use ($like): void {
            $busca->whereLike('titulo', $like)
                ->orWhereLike('resumo', $like)
                ->orWhereLike('doi', $like)
                ->orWhereLike('seo_titulo', $like)
                ->orWhereLike('seo_palavras_chave', $like)
                ->orWhereHas(
                    'autores',
                    static fn (Builder $autores): Builder => $autores->whereLike('nome', $like),
                )
                ->orWhereHas(
                    'palavrasChave',
                    static fn (Builder $palavras): Builder => $palavras->whereLike('nome', $like),
                )
                ->orWhereHas(
                    'areasTematicas',
                    static fn (Builder $areas): Builder => $areas->whereLike('nome', $like),
                );
        });
    }

    public function estaPublicado(): bool
    {
        return $this->status === StatusArtigo::Publicado;
    }

    /**
     * @param  list<string>  $nomes
     */
    public function sincronizarAutores(array $nomes): void
    {
        $this->autores()->delete();

        foreach ($nomes as $ordem => $nome) {
            $this->autores()->create([
                'nome' => $nome,
                'ordem' => $ordem,
            ]);
        }
    }

    /**
     * @param  list<int|string>  $ids
     */
    public function sincronizarCategorias(array $ids): void
    {
        $this->categorias()->sync($this->idsUnicos($ids));
    }

    /**
     * @param  list<string>  $nomes
     */
    public function sincronizarPalavrasChave(array $nomes): void
    {
        $this->palavrasChave()->sync($this->idsUnicosPorNome($nomes, PalavraChave::class));
    }

    /**
     * @param  list<string>  $nomes
     */
    public function sincronizarAreasTematicas(array $nomes): void
    {
        $this->areasTematicas()->sync($this->idsUnicosPorNome($nomes, AreaTematica::class));
    }

    /**
     * @param  list<string>  $nomes
     */
    public function sincronizarEtiquetas(array $nomes): void
    {
        $this->etiquetas()->sync($this->idsUnicosPorNome($nomes, Etiqueta::class));
    }

    /**
     * @param  list<string>  $referencias
     */
    public function sincronizarReferencias(array $referencias): void
    {
        $this->referencias()->delete();

        foreach ($referencias as $ordem => $texto) {
            $this->referencias()->create([
                'texto' => $texto,
                'ordem' => $ordem,
            ]);
        }
    }

    /**
     * @param  list<array{id?: int, titulo: string, url?: string|null, arquivo?: UploadedFile|null}>  $documentos
     */
    public function sincronizarDocumentos(array $documentos): void
    {
        $existentes = $this->documentos()->get()->keyBy('id');
        $idsMantidos = [];

        foreach ($documentos as $ordem => $item) {
            $id = isset($item['id']) ? (int) $item['id'] : null;
            $url = $this->textoOpcional($item['url'] ?? null);
            $arquivoUpload = ($item['arquivo'] ?? null) instanceof UploadedFile
                ? $item['arquivo']
                : null;

            /** @var ArtigoDocumento|null $documento */
            $documento = $id !== null && $id > 0
                ? $existentes->get($id)
                : null;

            if ($documento === null) {
                $documento = new ArtigoDocumento(['artigo_id' => $this->id]);
            }

            $documento->titulo = trim((string) $item['titulo']);
            $documento->ordem = (int) $ordem;

            if ($arquivoUpload !== null) {
                $documento->arquivo = $this->armazenarDocumento($arquivoUpload, $documento->arquivo);
                $documento->url = null;
            } elseif ($url !== null) {
                $this->excluirArquivoPublico($documento->arquivo);
                $documento->arquivo = null;
                $documento->url = $url;
            }

            $documento->save();
            $idsMantidos[] = $documento->id;
        }

        $existentes
            ->reject(fn (ArtigoDocumento $documento): bool => in_array($documento->id, $idsMantidos, true))
            ->each(function (ArtigoDocumento $documento): void {
                $this->excluirArquivoPublico($documento->arquivo);
                $documento->delete();
            });
    }

    public function excluirPor(User $usuario): void
    {
        $this->forceFill([
            'deletado_por' => $usuario->id,
        ])->save();

        $this->delete();
    }

    public function restaurar(): void
    {
        $this->restore();

        $this->forceFill([
            'deletado_por' => null,
        ])->save();
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<int>
     */
    private function idsUnicos(array $ids): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => (int) $id, $ids),
            static fn (int $id): bool => $id > 0,
        )));
    }

    /**
     * @param  list<string>  $nomes
     * @param  class-string<PalavraChave|AreaTematica|Etiqueta>  $modelClass
     * @return list<int>
     */
    private function idsUnicosPorNome(array $nomes, string $modelClass): array
    {
        $ids = [];
        $vistos = [];

        foreach ($nomes as $nome) {
            $chave = Str::lower($nome);

            if (isset($vistos[$chave])) {
                continue;
            }

            $vistos[$chave] = true;
            $ids[] = $modelClass::encontrarOuCriarPorNome($nome)->id;
        }

        return $ids;
    }

    private function armazenarDocumento(UploadedFile $arquivo, ?string $arquivoAnterior = null): string
    {
        $extensao = strtolower($arquivo->getClientOriginalExtension() ?: $arquivo->extension() ?: 'bin');
        $caminho = 'artigos/documentos/'.Str::uuid()->toString().'.'.$extensao;

        Storage::disk('public')->putFileAs(
            dirname($caminho),
            $arquivo,
            basename($caminho),
        );

        $this->excluirArquivoPublico($arquivoAnterior);

        return $caminho;
    }

    private function excluirArquivoPublico(?string $caminho): void
    {
        if ($caminho === null || $caminho === '' || $this->ehUrlExterna($caminho)) {
            return;
        }

        Storage::disk('public')->delete($caminho);
    }

    private function ehUrlExterna(string $caminho): bool
    {
        return str_starts_with($caminho, 'http://') || str_starts_with($caminho, 'https://');
    }

    private function textoOpcional(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $texto = trim((string) $value);

        return $texto !== '' ? $texto : null;
    }
}
