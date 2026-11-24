<?php

namespace App\Models;

use App\Enums\StatusImportacaoArtigo;
use Database\Factories\ArtigoImportacaoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ArtigoImportacao extends Model
{
    /** @use HasFactory<ArtigoImportacaoFactory> */
    use HasFactory;

    protected $table = 'artigo_importacoes';

    protected $fillable = [
        'user_id',
        'artigo_id',
        'pdf_path',
        'pdf_nome_original',
        'status',
        'progresso',
        'etapa',
        'erro',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusImportacaoArtigo::class,
            'progresso' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function artigo(): BelongsTo
    {
        return $this->belongsTo(Artigo::class);
    }

    public function marcarComoProcessando(): void
    {
        $this->forceFill([
            'status' => StatusImportacaoArtigo::Processando,
            'progresso' => max(5, (int) $this->progresso),
            'etapa' => $this->etapa ?: 'Iniciando importação...',
            'erro' => null,
        ])->save();
    }

    public function atualizarProgresso(int $progresso, string $etapa): void
    {
        $this->forceFill([
            'status' => StatusImportacaoArtigo::Processando,
            'progresso' => max(0, min(99, $progresso)),
            'etapa' => $etapa,
            'erro' => null,
        ])->save();
    }

    public function marcarComoConcluida(Artigo $artigo): void
    {
        $this->forceFill([
            'status' => StatusImportacaoArtigo::Concluida,
            'artigo_id' => $artigo->id,
            'progresso' => 100,
            'etapa' => 'Concluída',
            'erro' => null,
        ])->save();
    }

    public function marcarComoFalhou(string $mensagem): void
    {
        $this->forceFill([
            'status' => StatusImportacaoArtigo::Falhou,
            'etapa' => 'Falhou',
            'erro' => $mensagem,
        ])->save();
    }

    public function excluirPdfTemporario(): void
    {
        if ($this->pdf_path === '' || $this->pdf_path === null) {
            return;
        }

        Storage::disk('public')->delete($this->pdf_path);
    }
}
