<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artigo_categoria', function (Blueprint $table) {
            $table->foreignId('artigo_id')->constrained('artigos')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();

            $table->primary(['artigo_id', 'categoria_id']);
        });

        if (! Schema::hasColumn('artigos', 'categoria_id')) {
            return;
        }

        DB::table('artigos')
            ->whereNotNull('categoria_id')
            ->orderBy('id')
            ->select(['id', 'categoria_id'])
            ->chunkById(200, function ($artigos): void {
                $linhas = $artigos
                    ->map(fn ($artigo): array => [
                        'artigo_id' => $artigo->id,
                        'categoria_id' => $artigo->categoria_id,
                    ])
                    ->all();

                if ($linhas !== []) {
                    DB::table('artigo_categoria')->insertOrIgnore($linhas);
                }
            });

        Schema::table('artigos', function (Blueprint $table): void {
            $table->dropForeign(['categoria_id']);
        });

        $temIndiceExtra = collect(Schema::getIndexes('artigos'))->contains(
            fn (array $index): bool => ($index['name'] ?? '') === 'artigos_categoria_id_index',
        );

        if ($temIndiceExtra) {
            Schema::table('artigos', function (Blueprint $table): void {
                $table->dropIndex('artigos_categoria_id_index');
            });
        }

        Schema::table('artigos', function (Blueprint $table): void {
            $table->dropColumn('categoria_id');
        });
    }

    public function down(): void
    {
        Schema::table('artigos', function (Blueprint $table): void {
            $table->foreignId('categoria_id')->nullable()->after('cadastrante_id')->constrained('categorias')->nullOnDelete();
        });

        $pares = DB::table('artigo_categoria')
            ->orderBy('artigo_id')
            ->get(['artigo_id', 'categoria_id'])
            ->groupBy('artigo_id');

        foreach ($pares as $artigoId => $categorias) {
            DB::table('artigos')
                ->where('id', $artigoId)
                ->update(['categoria_id' => $categorias->first()->categoria_id]);
        }

        Schema::dropIfExists('artigo_categoria');
    }
};
