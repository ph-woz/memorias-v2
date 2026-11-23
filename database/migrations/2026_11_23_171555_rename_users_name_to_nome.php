<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'name') || Schema::hasColumn('users', 'nome')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->renameColumn('name', 'nome');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'nome') || Schema::hasColumn('users', 'name')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->renameColumn('nome', 'name');
        });
    }
};
