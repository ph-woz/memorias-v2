<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('slogan')->nullable();
            $table->string('imagem');
            $table->string('overlay_cor', 7)->default('#000000');
            $table->unsignedTinyInteger('overlay_opacidade')->default(45);
            $table->string('posicao_horizontal', 20)->default('esquerda');
            $table->string('posicao_vertical', 20)->default('baixo');
            $table->string('cta_texto')->nullable();
            $table->string('cta_link')->nullable();
            $table->unsignedInteger('ordem')->default(0)->index();
            $table->boolean('ativo')->default(true)->index();
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->timestamp('excluido_em')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
