<?php

namespace App\Actions\Banners;

use App\Models\Banner;
use Illuminate\Support\Facades\Storage;

class DeleteBannerAction
{
    public function execute(Banner $banner): void
    {
        if (
            $banner->imagem
            && ! str_starts_with($banner->imagem, 'http://')
            && ! str_starts_with($banner->imagem, 'https://')
        ) {
            Storage::disk('public')->delete($banner->imagem);
        }

        $banner->delete();
    }
}
