<?php

namespace Database\Factories;

use App\Enums\StatusImportacaoArtigo;
use App\Models\ArtigoImportacao;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ArtigoImportacao>
 */
class ArtigoImportacaoFactory extends Factory
{
    protected $model = ArtigoImportacao::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'artigo_id' => null,
            'pdf_path' => 'artigos/importacoes/'.Str::uuid()->toString().'.pdf',
            'pdf_nome_original' => 'artigo.pdf',
            'status' => StatusImportacaoArtigo::Pendente,
            'progresso' => 0,
            'etapa' => 'Na fila...',
            'erro' => null,
        ];
    }

    public function processando(): static
    {
        return $this->state(fn (): array => [
            'status' => StatusImportacaoArtigo::Processando,
            'progresso' => 35,
            'etapa' => 'Extraindo dados com IA...',
        ]);
    }

    public function falhou(): static
    {
        return $this->state(fn (): array => [
            'status' => StatusImportacaoArtigo::Falhou,
            'erro' => 'Falha de teste',
        ]);
    }
}
