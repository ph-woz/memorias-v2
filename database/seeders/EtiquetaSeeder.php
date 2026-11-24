<?php

namespace Database\Seeders;

use App\Models\Etiqueta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EtiquetaSeeder extends Seeder
{
    public function run(): void
    {
        $etiquetas = [
            ['nome' => 'Destaque', 'cor' => '#EF4444'],
            ['nome' => 'Novidade', 'cor' => '#0EA5E9'],
            ['nome' => 'Entrevista', 'cor' => '#8B5CF6'],
            ['nome' => 'Ensaio', 'cor' => '#10B981'],
            ['nome' => 'Arquivo', 'cor' => '#F59E0B'],
            ['nome' => 'Editorial', 'cor' => '#6366F1'],
        ];

        foreach ($etiquetas as $etiqueta) {
            Etiqueta::query()->firstOrCreate(
                ['slug' => Str::slug($etiqueta['nome'])],
                [
                    'nome' => $etiqueta['nome'],
                    'cor' => $etiqueta['cor'],
                ],
            );
        }
    }
}
