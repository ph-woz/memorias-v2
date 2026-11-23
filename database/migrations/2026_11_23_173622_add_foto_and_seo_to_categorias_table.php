<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->string('foto')->nullable()->after('identificador');
            $table->string('seo_titulo')->nullable()->after('foto');
            $table->string('seo_descricao', 500)->nullable()->after('seo_titulo');
            $table->string('seo_palavras_chave')->nullable()->after('seo_descricao');
        });
    }

    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropColumn([
                'foto',
                'seo_titulo',
                'seo_descricao',
                'seo_palavras_chave',
            ]);
        });
    }
};
