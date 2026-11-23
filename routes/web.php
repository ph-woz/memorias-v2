<?php

use App\Http\Controllers\ArtigoController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PalavraChaveController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::resource('artigos', ArtigoController::class)
        ->parameters(['artigos' => 'artigo'])
        ->names('artigos')
        ->except(['show']);

    Route::post('banners/reordenar', [BannerController::class, 'reorder'])
        ->name('banners.reorder');

    Route::resource('banners', BannerController::class)
        ->parameters(['banners' => 'banner'])
        ->names('banners')
        ->except(['show']);

    Route::resource('categorias', CategoriaController::class)
        ->parameters(['categorias' => 'categoria'])
        ->names('categorias')
        ->except(['show']);

    Route::resource('usuarios', UsuarioController::class)
        ->parameters(['usuarios' => 'usuario'])
        ->names('usuarios')
        ->except(['show']);

    Route::resource('palavras-chave', PalavraChaveController::class)
        ->parameters(['palavras-chave' => 'palavraChave'])
        ->names('palavras_chave')
        ->except(['show']);
});

Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
