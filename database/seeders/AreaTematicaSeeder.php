<?php

namespace Database\Seeders;

use App\Models\AreaTematica;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AreaTematicaSeeder extends Seeder
{
    public function run(): void
    {
        $nomes = [
            'História',
            'Memória coletiva',
            'Cultura popular',
            'Educação',
            'Saúde',
            'Meio ambiente',
            'Direitos humanos',
            'Identidade',
            'Gênero',
            'Raça e etnia',
            'Migração',
            'Trabalho',
            'Cidade',
            'Campo',
            'Tecnologia',
        ];

        foreach ($nomes as $nome) {
            AreaTematica::query()->firstOrCreate(
                ['slug' => Str::slug($nome)],
                ['nome' => $nome],
            );
        }
    }
}
