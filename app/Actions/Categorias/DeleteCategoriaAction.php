<?php

namespace App\Actions\Categorias;

use App\Models\Categoria;
use Illuminate\Support\Facades\Storage;

class DeleteCategoriaAction
{
    public function execute(Categoria $categoria): void
    {
        if (
            $categoria->foto
            && ! str_starts_with($categoria->foto, 'http://')
            && ! str_starts_with($categoria->foto, 'https://')
        ) {
            Storage::disk('public')->delete($categoria->foto);
        }

        $categoria->delete();
    }
}
