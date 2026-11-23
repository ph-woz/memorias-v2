<?php

namespace App\Actions\PalavrasChave;

use App\Models\PalavraChave;

class DeletePalavraChaveAction
{
    public function execute(PalavraChave $palavraChave): void
    {
        $palavraChave->delete();
    }
}
