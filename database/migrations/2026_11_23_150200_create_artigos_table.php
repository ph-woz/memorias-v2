<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artigos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->string('titulo');
            $table->string('identificador')->unique();
            $table->string('resumo', 500)->nullable();
            $table->longText('conteudo');
            $table->string('status', 20)->default('rascunho')->index();
            $table->timestamp('publicado_em')->nullable()->index();
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->timestamp('excluido_em')->nullable()->index();

            $table->index(['status', 'publicado_em']);
            $table->index('usuario_id');
            $table->index('categoria_id');
        });

        Schema::create('artigo_palavra_chave', function (Blueprint $table) {
            $table->foreignId('artigo_id')->constrained('artigos')->cascadeOnDelete();
            $table->foreignId('palavra_chave_id')->constrained('palavras_chave')->cascadeOnDelete();

            $table->primary(['artigo_id', 'palavra_chave_id']);
        });

        Schema::create('artigo_usuario', function (Blueprint $table) {
            $table->foreignId('artigo_id')->constrained('artigos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();

            $table->primary(['artigo_id', 'usuario_id']);
            $table->index('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artigo_usuario');
        Schema::dropIfExists('artigo_palavra_chave');
        Schema::dropIfExists('artigos');
    }
};
