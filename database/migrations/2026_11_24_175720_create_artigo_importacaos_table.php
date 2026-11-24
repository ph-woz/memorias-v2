<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artigo_importacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('artigo_id')->nullable()->constrained('artigos')->nullOnDelete();
            $table->string('pdf_path');
            $table->string('pdf_nome_original');
            $table->string('status', 20)->default('pendente')->index();
            $table->text('erro')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artigo_importacoes');
    }
};
