<?php

namespace App\Providers;

use App\Models\MenuItem;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.site', function ($view): void {
            $view->with(
                'menuItens',
                MenuItem::query()
                    ->ativos()
                    ->raizes()
                    ->with([
                        'filhos' => fn ($query) => $query->ativos()->ordenados(),
                    ])
                    ->ordenados()
                    ->get([
                        'id',
                        'parent_id',
                        'rotulo',
                        'url',
                        'target_blank',
                        'ordem',
                        'ativo',
                    ]),
            );
        });
    }
}
