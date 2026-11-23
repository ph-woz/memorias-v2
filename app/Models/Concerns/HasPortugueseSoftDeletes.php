<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;

trait HasPortugueseSoftDeletes
{
    use SoftDeletes;

    public const DELETED_AT = 'excluido_em';
}
