<?php

namespace App\Actions\Categorias;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoreCategoriaFotoAction
{
    public function execute(UploadedFile $arquivo, ?string $fotoAnterior = null): string
    {
        $caminho = $arquivo->store('categorias', 'public');

        if ($fotoAnterior !== null && $fotoAnterior !== '' && ! $this->ehUrlExterna($fotoAnterior)) {
            Storage::disk('public')->delete($fotoAnterior);
        }

        return $caminho;
    }

    private function ehUrlExterna(string $caminho): bool
    {
        return str_starts_with($caminho, 'http://') || str_starts_with($caminho, 'https://');
    }
}
