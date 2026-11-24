<?php

namespace App\Support\Artigos;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;
use ImagickException;
use Throwable;

/**
 * Extrai figuras embutidas (JPEG/PNG) do PDF e, se necessário, páginas rasterizadas.
 *
 * @phpstan-type ImagemExtraida array{path: string, url: string, alt: string, bytes: int}
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
        $imagens = $this->extrairStreamsRaster($binario);

        if ($imagens === []) {
            $imagens = $this->rasterizarPaginasComFiguras($absoluto);
        }

        return $imagens;
    }

    /**
     * @return list<ImagemExtraida>
     */
    private function extrairStreamsRaster(string $binario): array
    {
        $imagens = [];
        $diretorio = 'artigos/conteudo/'.Str::uuid()->toString();

        // JPEGs embutidos (DCTDecode).
        if (preg_match_all('/\xFF\xD8\xFF[\s\S]{200,}?\xFF\xD9/', $binario, $jpegs) > 0) {
            foreach ($jpegs[0] as $indice => $jpeg) {
                if (strlen($jpeg) < 8_000) {
                    continue;
                }

                $imagens[] = $this->gravar($diretorio, 'fig-'.($indice + 1).'.jpg', $jpeg, 'image/jpeg');
            }
        }

        // PNGs embutidos.
        if (preg_match_all('/\x89PNG\r\n\x1a\n[\s\S]{200,}?IEND\xaeB`\x82/', $binario, $pngs) > 0) {
            foreach ($pngs[0] as $indice => $png) {
                if (strlen($png) < 8_000) {
                    continue;
                }

                $imagens[] = $this->gravar($diretorio, 'fig-png-'.($indice + 1).'.png', $png, 'image/png');
            }
        }

        return $this->filtrarDuplicatas($imagens);
    }

    /**
     * @return list<ImagemExtraida>
     */
    private function rasterizarPaginasComFiguras(string $caminhoAbsoluto): array
    {
        if (! class_exists(Imagick::class)) {
            return [];
        }

        try {
            $pdf = new Imagick;
            $pdf->setResolution(120, 120);
            $pdf->readImage($caminhoAbsoluto);
        } catch (ImagickException|Throwable) {
            return [];
        }

        $diretorio = 'artigos/conteudo/'.Str::uuid()->toString();
        $imagens = [];
        $pagina = 0;

        foreach ($pdf as $frame) {
            $pagina++;

            // Páginas 1 (capa/metadados) e últimas (refs) raramente são figuras úteis.
            if ($pagina === 1 || $pagina > max(2, $pdf->getNumberImages() - 1)) {
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

            if (count($imagens) >= 4) {
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
