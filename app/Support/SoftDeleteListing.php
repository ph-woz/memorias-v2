<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class SoftDeleteListing
{
    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function apply(Builder $query, bool $excluidos): Builder
    {
        return $excluidos
            ? $query->onlyTrashed()
            : $query;
    }
}
