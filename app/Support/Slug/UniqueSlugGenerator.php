<?php

namespace App\Support\Slug;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class UniqueSlugGenerator
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function generate(string $modelClass, string $value, ?int $ignoreId = null, string $column = 'slug'): string
    {
        $base = Str::slug($value);
        $base = $base !== '' ? $base : 'item';
        $slug = $base;
        $suffix = 1;

        while ($this->exists($modelClass, $column, $slug, $ignoreId)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function exists(string $modelClass, string $column, string $slug, ?int $ignoreId): bool
    {
        $query = $modelClass::query()->where($column, $slug);

        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query->withTrashed();
        }

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }
}
