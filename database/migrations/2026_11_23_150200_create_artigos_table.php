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
            $table->foreignId('cadastrante_id')->constrained('users')->cascadeOnDelete();
            $table->string('titulo');
            $table->string('slug')->unique();
            $table->string('doi')->nullable();
            $table->string('foto')->nullable();
            $table->string('resumo', 500)->nullable();
            $table->longText('conteudo');
            $table->text('endereco_instituicoes')->nullable();
            $table->text('autor_correspondente')->nullable();
            $table->string('seo_titulo')->nullable();
            $table->string('seo_descricao', 500)->nullable();
            $table->string('seo_palavras_chave')->nullable();
            $table->string('status', 20)->default('rascunho')->index();
            $table->timestamp('publicado_em')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deletado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['status', 'publicado_em']);
            $table->index('cadastrante_id');
        });

        Schema::create('artigo_palavra_chave', function (Blueprint $table) {
            $table->foreignId('artigo_id')->constrained('artigos')->cascadeOnDelete();
            $table->foreignId('palavra_chave_id')->constrained('palavras_chave')->cascadeOnDelete();

            $table->primary(['artigo_id', 'palavra_chave_id']);
        });

        Schema::create('artigo_autores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artigo_id')->constrained('artigos')->cascadeOnDelete();
            $table->string('nome');
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();

            $table->index(['artigo_id', 'ordem']);
        });

        Schema::create('artigo_referencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artigo_id')->constrained('artigos')->cascadeOnDelete();
            $table->text('texto');
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();

            $table->index(['artigo_id', 'ordem']);
        });

        Schema::create('artigo_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artigo_id')->constrained('artigos')->cascadeOnDelete();
            $table->string('titulo');
            $table->string('arquivo')->nullable();
            $table->string('url', 2048)->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();

            $table->index(['artigo_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artigo_documentos');
        Schema::dropIfExists('artigo_referencias');
        Schema::dropIfExists('artigo_autores');
        Schema::dropIfExists('artigo_palavra_chave');
        Schema::dropIfExists('artigos');
    }
};
