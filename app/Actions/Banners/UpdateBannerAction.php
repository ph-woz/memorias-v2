<?php

namespace App\Actions\Banners;

use App\DataTransferObjects\BannerData;
use App\Models\Banner;

class UpdateBannerAction
{
    public function __construct(
        private readonly StoreBannerImageAction $storeBannerImageAction,
    ) {}

    public function execute(Banner $banner, BannerData $data): Banner
    {
        $atributos = [
            'titulo' => $data->titulo,
            'slogan' => $data->slogan,
            'overlay_cor' => $data->overlayCor,
            'overlay_opacidade' => $data->overlayOpacidade,
            'posicao_horizontal' => $data->posicaoHorizontal,
            'posicao_vertical' => $data->posicaoVertical,
            'cta_texto' => $data->ctaTexto,
            'cta_link' => $data->ctaLink,
            'ativo' => $data->ativo,
        ];

        if ($data->imagemArquivo !== null) {
            $atributos['imagem'] = $this->storeBannerImageAction->execute(
                $data->imagemArquivo,
                $banner->imagem,
            );
        }

        $banner->update($atributos);

        return $banner->refresh();
    }
}
