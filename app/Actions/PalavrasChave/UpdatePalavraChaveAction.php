<?php

namespace App\Actions\PalavrasChave;

use App\DataTransferObjects\PalavraChaveData;
use App\Models\PalavraChave;
use App\Support\Slug\UniqueSlugGenerator;

class UpdatePalavraChaveAction
{
    public function __construct(
        private readonly UniqueSlugGenerator $slugGenerator,
    ) {}

    public function execute(PalavraChave $palavraChave, PalavraChaveData $data): PalavraChave
    {
        $identificador = $palavraChave->identificador === $data->identificador
            && $palavraChave->nome === $data->nome
            ? $palavraChave->identificador
            : $this->slugGenerator->generate(
                PalavraChave::class,
                $data->identificador,
                $palavraChave->id,
            );

        $palavraChave->update([
            'nome' => $data->nome,
            'identificador' => $identificador,
        ]);

        return $palavraChave->refresh();
    }
}
