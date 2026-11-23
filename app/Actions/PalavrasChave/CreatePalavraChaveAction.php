<?php

namespace App\Actions\PalavrasChave;

use App\DataTransferObjects\PalavraChaveData;
use App\Models\PalavraChave;
use App\Support\Slug\UniqueSlugGenerator;

class CreatePalavraChaveAction
{
    public function __construct(
        private readonly UniqueSlugGenerator $slugGenerator,
    ) {}

    public function execute(PalavraChaveData $data): PalavraChave
    {
        return PalavraChave::query()->create([
            'nome' => $data->nome,
            'identificador' => $this->slugGenerator->generate(
                PalavraChave::class,
                $data->identificador,
            ),
        ]);
    }
}
