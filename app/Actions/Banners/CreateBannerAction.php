<?php

namespace App\Actions\Banners;

use App\DataTransferObjects\BannerData;
use App\Models\Banner;
use RuntimeException;

class CreateBannerAction
{
    public function __construct(
        private readonly StoreBannerImageAction $storeBannerImageAction,
    ) {}

    public function execute(BannerData $data): Banner
    {
        if ($data->imagemArquivo === null) {
            throw new RuntimeException('A imagem do banner é obrigatória.');
        }

        $proximaOrdem = ((int) Banner::query()->max('ordem')) + 1;

        return Banner::query()->create([
            'titulo' => $data->titulo,
            'slogan' => $data->slogan,
            'imagem' => $this->storeBannerImageAction->execute($data->imagemArquivo),
            'overlay_cor' => $data->overlayCor,
            'overlay_opacidade' => $data->overlayOpacidade,
            'posicao_horizontal' => $data->posicaoHorizontal,
            'posicao_vertical' => $data->posicaoVertical,
            'cta_texto' => $data->ctaTexto,
            'cta_link' => $data->ctaLink,
            'ordem' => $proximaOrdem,
            'ativo' => $data->ativo,
        ]);
    }
}
