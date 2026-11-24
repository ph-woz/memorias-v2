<?php

namespace App\Models;

use Database\Factories\ArtigoDocumentoFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ArtigoDocumento extends Model
{
    /** @use HasFactory<ArtigoDocumentoFactory> */
    use HasFactory;

    protected $table = 'artigo_documentos';

    protected $fillable = [
        'artigo_id',
        'titulo',
        'arquivo',
        'url',
        'ordem',
    ];

    protected $appends = [
        'url_arquivo',
        'nome_arquivo',
    ];

    protected function casts(): array
    {
        return [
            'ordem' => 'integer',
        ];
    }

    protected function urlArquivo(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->arquivo !== null && $this->arquivo !== '') {
                if (str_starts_with($this->arquivo, 'http://') || str_starts_with($this->arquivo, 'https://')) {
                    return $this->arquivo;
                }

                return Storage::disk('public')->url($this->arquivo);
            }

            if ($this->url !== null && $this->url !== '') {
                return $this->url;
            }

            return null;
        });
    }

    protected function nomeArquivo(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->arquivo === null || $this->arquivo === '') {
                return null;
            }

            return basename($this->arquivo);
        });
    }

    /**
     * Rótulo curto para botões no site (PDF, HTML, DOC…), no estilo Memórias/IOC.
     */
    public function rotuloBotao(): string
    {
        $titulo = trim((string) $this->titulo);

        if ($titulo !== '' && mb_strlen($titulo) <= 12) {
            return mb_strtoupper($titulo);
        }

        $origem = $this->arquivo ?: $this->url ?: $titulo;
        $extensao = strtolower(pathinfo((string) $origem, PATHINFO_EXTENSION));

        return match ($extensao) {
            'pdf' => 'PDF',
            'html', 'htm' => 'HTML',
            'doc', 'docx' => 'DOC',
            'odt' => 'ODT',
            'rtf' => 'RTF',
            'txt' => 'TXT',
            'zip' => 'ZIP',
            'jpg', 'jpeg', 'png', 'webp', 'gif' => 'IMG',
            default => $titulo !== ''
                ? mb_strtoupper(mb_substr($titulo, 0, 12))
                : 'LINK',
        };
    }

    public function artigo(): BelongsTo
    {
        return $this->belongsTo(Artigo::class);
    }
}
