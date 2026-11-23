<?php

namespace App\Actions\Banners;

use App\Models\Banner;
use Illuminate\Support\Facades\DB;

class ReorderBannersAction
{
    /**
     * @param  list<int>  $bannerIds
     */
    public function execute(array $bannerIds): void
    {
        DB::transaction(function () use ($bannerIds): void {
            foreach (array_values($bannerIds) as $indice => $bannerId) {
                Banner::query()
                    ->whereKey($bannerId)
                    ->update(['ordem' => $indice + 1]);
            }
        });
    }
}
