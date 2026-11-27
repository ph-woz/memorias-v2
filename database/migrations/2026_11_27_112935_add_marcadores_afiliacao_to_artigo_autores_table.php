<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('artigo_autores', function (Blueprint $table) {
            $table->string('marcadores_afiliacao', 50)->nullable()->after('nome');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('artigo_autores', function (Blueprint $table) {
            $table->dropColumn('marcadores_afiliacao');
        });
    }
};
