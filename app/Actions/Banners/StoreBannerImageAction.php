<?php

namespace App\Actions\Banners;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoreBannerImageAction
{
    public function execute(UploadedFile $arquivo, ?string $imagemAnterior = null): string
    {
        $caminho = $arquivo->store('banners', 'public');

        if ($imagemAnterior !== null && $imagemAnterior !== '' && ! $this->ehUrlExterna($imagemAnterior)) {
            Storage::disk('public')->delete($imagemAnterior);
        }

        return $caminho;
    }

    private function ehUrlExterna(string $caminho): bool
    {
        return str_starts_with($caminho, 'http://') || str_starts_with($caminho, 'https://');
    }
}
