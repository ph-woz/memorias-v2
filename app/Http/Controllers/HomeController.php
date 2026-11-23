<?php

namespace App\Http\Controllers;

use App\Queries\Banners\ListBannersQuery;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(ListBannersQuery $query): View
    {
        return view('home', [
            'banners' => $query->ativosParaHome(),
        ]);
    }
}
