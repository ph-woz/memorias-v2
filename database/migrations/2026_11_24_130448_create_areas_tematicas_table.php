<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas_tematicas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('artigo_area_tematica', function (Blueprint $table) {
            $table->foreignId('artigo_id')->constrained('artigos')->cascadeOnDelete();
            $table->foreignId('area_tematica_id')->constrained('areas_tematicas')->cascadeOnDelete();

            $table->primary(['artigo_id', 'area_tematica_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artigo_area_tematica');
        Schema::dropIfExists('areas_tematicas');
    }
};
