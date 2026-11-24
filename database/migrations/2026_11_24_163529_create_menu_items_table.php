<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('menu_itens')->cascadeOnDelete();
            $table->string('rotulo');
            $table->string('url', 2048)->nullable();
            $table->boolean('target_blank')->default(false);
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'ordem']);
            $table->index(['ativo', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_itens');
    }
};
