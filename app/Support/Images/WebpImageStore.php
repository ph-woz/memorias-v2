<?php

namespace App\Support\Images;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class WebpImageStore
{
    private const int Qualidade = 82;

    public function store(
        UploadedFile $arquivo,
        string $diretorio,
        int $ladoMaximo = 1200,
        ?string $anterior = null,
    ): string {
        $caminho = $this->converter($arquivo, trim($diretorio, '/').'/', $ladoMaximo);

        if ($anterior !== null && $anterior !== '' && ! $this->ehUrlExterna($anterior)) {
            Storage::disk('public')->delete($anterior);
        }

        return $caminho;
    }

    private function converter(UploadedFile $arquivo, string $diretorio, int $ladoMaximo): string
    {
        $conteudo = file_get_contents($arquivo->getRealPath());

        if ($conteudo === false) {
            throw new RuntimeException('Não foi possível ler o arquivo da imagem.');
        }

        $imagem = @imagecreatefromstring($conteudo);

        if ($imagem === false) {
            throw new RuntimeException('A imagem enviada não é válida.');
        }

        $largura = imagesx($imagem);
        $altura = imagesy($imagem);
        $maiorLado = max($largura, $altura);

        if ($maiorLado > $ladoMaximo) {
            $escala = $ladoMaximo / $maiorLado;
            $novaLargura = max(1, (int) round($largura * $escala));
            $novaAltura = max(1, (int) round($altura * $escala));
            $redimensionada = imagescale($imagem, $novaLargura, $novaAltura);

            if ($redimensionada === false) {
                imagedestroy($imagem);

                throw new RuntimeException('Não foi possível redimensionar a imagem.');
            }

            imagedestroy($imagem);
            $imagem = $redimensionada;
        }

        if (! imageistruecolor($imagem)) {
            imagepalettetotruecolor($imagem);
        }

        imagealphablending($imagem, true);
        imagesavealpha($imagem, true);

        ob_start();
        $ok = imagewebp($imagem, null, self::Qualidade);
        $binario = ob_get_clean();
        imagedestroy($imagem);

        if (! $ok || $binario === false || $binario === '') {
            throw new RuntimeException('Não foi possível converter a imagem para WebP.');
        }

        $caminho = $diretorio.Str::uuid()->toString().'.webp';
        Storage::disk('public')->put($caminho, $binario);

        return $caminho;
    }

    private function ehUrlExterna(string $caminho): bool
    {
        return str_starts_with($caminho, 'http://') || str_starts_with($caminho, 'https://');
    }
}
