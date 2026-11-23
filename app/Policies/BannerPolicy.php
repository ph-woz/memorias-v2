<?php

namespace App\Policies;

use App\Models\Banner;
use App\Models\User;

class BannerPolicy
{
    public function viewAny(User $usuario): bool
    {
        return true;
    }

    public function view(User $usuario, Banner $banner): bool
    {
        return true;
    }

    public function create(User $usuario): bool
    {
        return true;
    }

    public function update(User $usuario, Banner $banner): bool
    {
        return true;
    }

    public function reorder(User $usuario): bool
    {
        return true;
    }

    public function delete(User $usuario, Banner $banner): bool
    {
        return true;
    }
}
