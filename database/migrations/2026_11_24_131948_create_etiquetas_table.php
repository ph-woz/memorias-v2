<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etiquetas', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->string('slug')->unique();
            $table->string('cor', 7);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('artigo_etiqueta', function (Blueprint $table) {
            $table->foreignId('artigo_id')->constrained('artigos')->cascadeOnDelete();
            $table->foreignId('etiqueta_id')->constrained('etiquetas')->cascadeOnDelete();

            $table->primary(['artigo_id', 'etiqueta_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artigo_etiqueta');
        Schema::dropIfExists('etiquetas');
    }
};
