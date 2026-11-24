<?php

use App\Http\Controllers\AreaTematicaController;
use App\Http\Controllers\ArtigoController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EtiquetaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\PaginaController;
use App\Http\Controllers\PalavraChaveController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Site\AreaTematicaController as SiteAreaTematicaController;
use App\Http\Controllers\Site\ArtigoController as SiteArtigoController;
use App\Http\Controllers\Site\BuscaController as SiteBuscaController;
use App\Http\Controllers\Site\CategoriaController as SiteCategoriaController;
use App\Http\Controllers\Site\PaginaController as SitePaginaController;
use App\Http\Controllers\UsuarioController;
use App\Models\Pagina;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/busca', SiteBuscaController::class)->name('site.busca');
Route::get('/article/{artigo:slug}', SiteArtigoController::class)->name('site.artigos.show');
Route::get('/categoria/{categoria:slug}', SiteCategoriaController::class)->name('site.categorias.show');
Route::get('/area/{areaTematica:slug}', SiteAreaTematicaController::class)->name('site.areas_tematicas.show');

Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::post('artigos/{artigo}/restaurar', [ArtigoController::class, 'restore'])
        ->withTrashed()
        ->name('artigos.restore');

    Route::post('artigos/conteudo/imagens', [ArtigoController::class, 'storeConteudoImagem'])
        ->name('artigos.conteudo.imagens.store');

    Route::post('artigos/importar-pdf', [ArtigoController::class, 'importarPdf'])
        ->name('artigos.importar-pdf');

    Route::get('artigos/importacoes/{importacao}', [ArtigoController::class, 'showImportacao'])
        ->name('artigos.importacoes.show');

    Route::get('artigos/importacoes/{importacao}/status', [ArtigoController::class, 'statusImportacao'])
        ->name('artigos.importacoes.status');

    Route::resource('artigos', ArtigoController::class)
        ->parameters(['artigos' => 'artigo'])
        ->names('artigos')
        ->except(['show']);

    Route::post('banners/reordenar', [BannerController::class, 'reorder'])
        ->name('banners.reorder');

    Route::post('banners/{banner}/restaurar', [BannerController::class, 'restore'])
        ->withTrashed()
        ->name('banners.restore');

    Route::resource('banners', BannerController::class)
        ->parameters(['banners' => 'banner'])
        ->names('banners')
        ->except(['show']);

    Route::post('categorias/{categoria}/restaurar', [CategoriaController::class, 'restore'])
        ->withTrashed()
        ->name('categorias.restore');

    Route::resource('categorias', CategoriaController::class)
        ->parameters(['categorias' => 'categoria'])
        ->names('categorias')
        ->except(['show']);

    Route::post('usuarios/{usuario}/restaurar', [UsuarioController::class, 'restore'])
        ->withTrashed()
        ->name('usuarios.restore');

    Route::resource('usuarios', UsuarioController::class)
        ->parameters(['usuarios' => 'usuario'])
        ->names('usuarios')
        ->except(['show']);

    Route::post('palavras-chave/{palavraChave}/restaurar', [PalavraChaveController::class, 'restore'])
        ->withTrashed()
        ->name('palavras_chave.restore');

    Route::resource('palavras-chave', PalavraChaveController::class)
        ->parameters(['palavras-chave' => 'palavraChave'])
        ->names('palavras_chave')
        ->except(['show']);

    Route::post('areas-tematicas/{areaTematica}/restaurar', [AreaTematicaController::class, 'restore'])
        ->withTrashed()
        ->name('areas_tematicas.restore');

    Route::resource('areas-tematicas', AreaTematicaController::class)
        ->parameters(['areas-tematicas' => 'areaTematica'])
        ->names('areas_tematicas')
        ->except(['show']);

    Route::post('etiquetas/{etiqueta}/restaurar', [EtiquetaController::class, 'restore'])
        ->withTrashed()
        ->name('etiquetas.restore');

    Route::resource('etiquetas', EtiquetaController::class)
        ->parameters(['etiquetas' => 'etiqueta'])
        ->names('etiquetas')
        ->except(['show']);

    Route::post('menu/reordenar', [MenuItemController::class, 'reorder'])
        ->name('menu.reorder');

    Route::post('menu/{menuItem}/restaurar', [MenuItemController::class, 'restore'])
        ->withTrashed()
        ->name('menu.restore');

    Route::resource('menu', MenuItemController::class)
        ->parameters(['menu' => 'menuItem'])
        ->names('menu')
        ->except(['show']);

    Route::post('paginas/{pagina}/restaurar', [PaginaController::class, 'restore'])
        ->withTrashed()
        ->name('paginas.restore');

    Route::post('paginas/conteudo/imagens', [PaginaController::class, 'storeConteudoImagem'])
        ->name('paginas.conteudo.imagens.store');

    Route::resource('paginas', PaginaController::class)
        ->parameters(['paginas' => 'pagina'])
        ->names('paginas')
        ->except(['show']);
});

Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::get('/{pagina:slug}', SitePaginaController::class)
    ->where(
        'pagina',
        '^(?!'.implode('|', array_map('preg_quote', Pagina::SLUGS_RESERVADOS)).')[a-z0-9]+(?:-[a-z0-9]+)*$',
    )
    ->name('site.paginas.show');
