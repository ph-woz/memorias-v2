<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paginas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('slug')->unique();
            $table->longText('conteudo')->nullable();
            $table->boolean('ativo')->default(true);
            $table->string('seo_titulo')->nullable();
            $table->string('seo_descricao', 320)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['ativo', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paginas');
    }
};
