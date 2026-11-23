<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'ultimo_acesso_em')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('ultimo_acesso_em')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'ultimo_acesso_em')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('ultimo_acesso_em');
        });
    }
};
