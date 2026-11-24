<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paginas', function (Blueprint $table): void {
            $table->string('seo_palavras_chave')->nullable()->after('seo_descricao');
        });
    }

    public function down(): void
    {
        Schema::table('paginas', function (Blueprint $table): void {
            $table->dropColumn('seo_palavras_chave');
        });
    }
};
