<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artigos', function (Blueprint $table) {
            $table->text('resumo')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('artigos', function (Blueprint $table) {
            $table->string('resumo', 500)->nullable()->change();
        });
    }
};
