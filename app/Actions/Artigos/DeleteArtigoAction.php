<?php

namespace App\Actions\Artigos;

use App\Models\Artigo;

class DeleteArtigoAction
{
    public function execute(Artigo $artigo): void
    {
        $artigo->delete();
    }
}
