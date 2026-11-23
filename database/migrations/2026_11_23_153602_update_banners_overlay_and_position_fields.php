<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('banners', 'overlay')) {
            return;
        }

        Schema::table('banners', function (Blueprint $table): void {
            $table->dropColumn('overlay');
        });

        Schema::table('banners', function (Blueprint $table): void {
            $table->string('overlay_cor', 7)->default('#000000')->after('imagem');
            $table->unsignedTinyInteger('overlay_opacidade')->default(45)->after('overlay_cor');
            $table->string('posicao_horizontal', 20)->default('esquerda')->after('overlay_opacidade');
            $table->string('posicao_vertical', 20)->default('baixo')->after('posicao_horizontal');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('banners', 'overlay') || ! Schema::hasColumn('banners', 'overlay_cor')) {
            return;
        }

        Schema::table('banners', function (Blueprint $table): void {
            $table->dropColumn([
                'overlay_cor',
                'overlay_opacidade',
                'posicao_horizontal',
                'posicao_vertical',
            ]);
        });

        Schema::table('banners', function (Blueprint $table): void {
            $table->boolean('overlay')->default(true)->after('imagem');
        });
    }
};
