<?php

namespace App\DataTransferObjects;

use App\Enums\PosicaoHorizontal;
use App\Enums\PosicaoVertical;
use Illuminate\Http\UploadedFile;

readonly class BannerData
{
    public function __construct(
        public string $titulo,
        public ?string $slogan,
        public ?UploadedFile $imagemArquivo,
        public string $overlayCor,
        public int $overlayOpacidade,
        public PosicaoHorizontal $posicaoHorizontal,
        public PosicaoVertical $posicaoVertical,
        public ?string $ctaTexto,
        public ?string $ctaLink,
        public bool $ativo,
    ) {}

    /**
     * @param  array{
     *     titulo: string,
     *     slogan?: string|null,
     *     imagem?: UploadedFile|null,
     *     overlay_cor?: string|null,
     *     overlay_opacidade?: int|string|null,
     *     posicao_horizontal?: string|null,
     *     posicao_vertical?: string|null,
     *     cta_texto?: string|null,
     *     cta_link?: string|null,
     *     ativo?: bool|int|string|null
     * }  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            titulo: $payload['titulo'],
            slogan: $payload['slogan'] ?? null,
            imagemArquivo: $payload['imagem'] ?? null,
            overlayCor: $payload['overlay_cor'] ?? '#000000',
            overlayOpacidade: (int) ($payload['overlay_opacidade'] ?? 45),
            posicaoHorizontal: PosicaoHorizontal::from($payload['posicao_horizontal'] ?? PosicaoHorizontal::Esquerda->value),
            posicaoVertical: PosicaoVertical::from($payload['posicao_vertical'] ?? PosicaoVertical::Baixo->value),
            ctaTexto: $payload['cta_texto'] ?? null,
            ctaLink: $payload['cta_link'] ?? null,
            ativo: filter_var($payload['ativo'] ?? true, FILTER_VALIDATE_BOOL),
        );
    }
}
