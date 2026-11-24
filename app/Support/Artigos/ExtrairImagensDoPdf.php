<?php

namespace App\Support\Artigos;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;
use ImagickException;
use ImagickPixel;
use Throwable;

/**
 * Extrai figuras embutidas (JPEG/PNG/FlateDecode) do PDF e, se necessário, páginas rasterizadas.
 *
 * @phpstan-type ImagemExtraida array{path: string, url: string, alt: string, bytes: int, mime?: string}
 */
class ExtrairImagensDoPdf
{
    /**
     * @return list<ImagemExtraida>
     */
    public function deCaminhoPublico(string $caminhoPdfPublico): array
    {
        if (! Storage::disk('public')->exists($caminhoPdfPublico)) {
            return [];
        }

        $absoluto = Storage::disk('public')->path($caminhoPdfPublico);
        $binario = (string) file_get_contents($absoluto);
        $diretorio = 'artigos/conteudo/'.Str::uuid()->toString();

        $imagens = $this->extrairStreamsRaster($binario, $diretorio);
        $imagens = array_merge($imagens, $this->extrairImagensFlateDecode($binario, $diretorio));

        // Sempre complementa com páginas rasterizadas quando há poucas figuras reais
        // (PDFs Memórias usam muito desenho vetorial / CMYK Flate).
        if (count($imagens) < 3) {
            $imagens = array_merge($imagens, $this->rasterizarPaginasComFiguras($absoluto, $diretorio));
        }

        return $this->filtrarDuplicatas($imagens);
    }

    /**
     * @return list<ImagemExtraida>
     */
    private function extrairStreamsRaster(string $binario, string $diretorio): array
    {
        $imagens = [];

        if (preg_match_all('/\xFF\xD8\xFF[\s\S]{200,}?\xFF\xD9/', $binario, $jpegs) > 0) {
            foreach ($jpegs[0] as $indice => $jpeg) {
                if (strlen($jpeg) < 8_000) {
                    continue;
                }

                $imagens[] = $this->gravar($diretorio, 'fig-'.($indice + 1).'.jpg', $jpeg, 'image/jpeg');
            }
        }

        if (preg_match_all('/\x89PNG\r\n\x1a\n[\s\S]{200,}?IEND\xaeB`\x82/', $binario, $pngs) > 0) {
            foreach ($pngs[0] as $indice => $png) {
                if (strlen($png) < 8_000) {
                    continue;
                }

                $imagens[] = $this->gravar($diretorio, 'fig-png-'.($indice + 1).'.png', $png, 'image/png');
            }
        }

        return $imagens;
    }

    /**
     * @return list<ImagemExtraida>
     */
    private function extrairImagensFlateDecode(string $binario, string $diretorio): array
    {
        if (! class_exists(Imagick::class)) {
            return [];
        }

        $imagens = [];
        $indice = 0;
        $offset = 0;

        while (true) {
            $posA = strpos($binario, '/Subtype /Image', $offset);
            $posB = strpos($binario, '/Subtype/Image', $offset);
            $pos = match (true) {
                $posA !== false && $posB !== false => min($posA, $posB),
                $posA !== false => $posA,
                $posB !== false => $posB,
                default => false,
            };

            if ($pos === false) {
                break;
            }

            $offset = $pos + 10;
            $janelaInicio = max(0, $pos - 500);
            $janela = substr($binario, $janelaInicio, 1200);
            $dictLocalStart = strrpos(substr($janela, 0, $pos - $janelaInicio + 20), '<<');

            if ($dictLocalStart === false) {
                continue;
            }

            $dictAbsStart = $janelaInicio + $dictLocalStart;
            $dictEnd = strpos($binario, '>>', $pos);

            if ($dictEnd === false || $dictEnd - $dictAbsStart > 2000) {
                continue;
            }

            $dict = substr($binario, $dictAbsStart, $dictEnd - $dictAbsStart + 2);

            if (! str_contains($dict, 'FlateDecode')) {
                continue;
            }

            $width = preg_match('/\/Width\s+(\d+)/', $dict, $mm) ? (int) $mm[1] : 0;
            $height = preg_match('/\/Height\s+(\d+)/', $dict, $mm) ? (int) $mm[1] : 0;

            if ($width < 400 || $height < 400) {
                continue;
            }

            $streamPos = strpos($binario, 'stream', $dictEnd);

            if ($streamPos === false || $streamPos - $dictEnd > 80) {
                continue;
            }

            if (! preg_match('/^stream\r?\n/', substr($binario, $streamPos, 10), $m)) {
                continue;
            }

            $dataStart = $streamPos + strlen($m[0]);
            $end = strpos($binario, 'endstream', $dataStart);

            if ($end === false) {
                continue;
            }

            $compressed = rtrim(substr($binario, $dataStart, $end - $dataStart), "\r\n");
            $raw = @gzuncompress($compressed);

            if ($raw === false) {
                $raw = @gzinflate($compressed);
            }

            if ($raw === false || strlen($raw) < 20_000) {
                continue;
            }

            $jpeg = $this->pixelsParaJpeg($raw, $width, $height);

            if ($jpeg === null) {
                continue;
            }

            $indice++;
            $imagens[] = $this->gravar($diretorio, 'fig-flate-'.$indice.'.jpg', $jpeg, 'image/jpeg');
            $offset = $end + 9;

            if ($indice >= 8) {
                break;
            }
        }

        return $imagens;
    }

    private function pixelsParaJpeg(string $raw, int $width, int $height): ?string
    {
        $pixels = $width * $height;
        $canais = (int) round(strlen($raw) / max(1, $pixels));

        if (! in_array($canais, [1, 3, 4], true) || strlen($raw) < $pixels * $canais) {
            return null;
        }

        try {
            $mapa = match ($canais) {
                1 => 'I',
                3 => 'RGB',
                4 => 'CMYK',
            };

            $bytes = array_values(unpack('C*', substr($raw, 0, $pixels * $canais)) ?: []);

            if (count($bytes) < $pixels * $canais) {
                return null;
            }

            $imagem = new Imagick;
            $imagem->newImage($width, $height, new ImagickPixel('white'));
            $imagem->importImagePixels(0, 0, $width, $height, $mapa, Imagick::PIXEL_CHAR, $bytes);

            if ($canais === 4) {
                $imagem->transformImageColorspace(Imagick::COLORSPACE_SRGB);
            }

            $imagem->setImageFormat('jpeg');
            $imagem->setImageCompressionQuality(85);
            $blob = $imagem->getImageBlob();
            $imagem->clear();
            $imagem->destroy();

            return strlen($blob) >= 8_000 ? $blob : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return list<ImagemExtraida>
     */
    private function rasterizarPaginasComFiguras(string $caminhoAbsoluto, string $diretorio): array
    {
        if (! class_exists(Imagick::class)) {
            return [];
        }

        try {
            $pdf = new Imagick;
            $pdf->setResolution(140, 140);
            $pdf->readImage($caminhoAbsoluto);
        } catch (ImagickException|Throwable) {
            return [];
        }

        $imagens = [];
        $pagina = 0;
        $total = $pdf->getNumberImages();

        foreach ($pdf as $frame) {
            $pagina++;

            // Capa e última página de refs costumam ser pouco úteis como figura.
            if ($pagina === 1 || ($total > 3 && $pagina === $total)) {
                continue;
            }

            try {
                $frame->setImageFormat('jpeg');
                $frame->setImageCompressionQuality(82);
                $blob = $frame->getImageBlob();
            } catch (Throwable) {
                continue;
            }

            if (strlen($blob) < 20_000) {
                continue;
            }

            $imagens[] = $this->gravar($diretorio, 'pagina-'.$pagina.'.jpg', $blob, 'image/jpeg');

            if (count($imagens) >= 6) {
                break;
            }
        }

        $pdf->clear();
        $pdf->destroy();

        return $imagens;
    }

    /**
     * @return ImagemExtraida
     */
    private function gravar(string $diretorio, string $nome, string $bytes, string $mime): array
    {
        $path = $diretorio.'/'.$nome;
        Storage::disk('public')->put($path, $bytes);

        return [
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'alt' => pathinfo($nome, PATHINFO_FILENAME),
            'bytes' => strlen($bytes),
            'mime' => $mime,
        ];
    }

    /**
     * @param  list<ImagemExtraida>  $imagens
     * @return list<ImagemExtraida>
     */
    private function filtrarDuplicatas(array $imagens): array
    {
        $vistos = [];
        $unicas = [];

        foreach ($imagens as $imagem) {
            $hash = md5_file(Storage::disk('public')->path($imagem['path'])) ?: $imagem['path'];

            if (isset($vistos[$hash])) {
                Storage::disk('public')->delete($imagem['path']);

                continue;
            }

            $vistos[$hash] = true;
            $unicas[] = $imagem;
        }

        return $unicas;
    }
}
