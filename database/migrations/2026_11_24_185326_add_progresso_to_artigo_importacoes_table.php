<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artigo_importacoes', function (Blueprint $table): void {
            $table->unsignedTinyInteger('progresso')->default(0)->after('status');
            $table->string('etapa')->nullable()->after('progresso');
        });
    }

    public function down(): void
    {
        Schema::table('artigo_importacoes', function (Blueprint $table): void {
            $table->dropColumn(['progresso', 'etapa']);
        });
    }
};
