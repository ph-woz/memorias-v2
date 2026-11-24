<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        if (MenuItem::query()->exists()) {
            return;
        }

        $itens = [
            ['rotulo' => 'Artigos', 'url' => '/#ultimos', 'ordem' => 1],
            ['rotulo' => 'Categorias', 'url' => '/#categorias', 'ordem' => 2],
            ['rotulo' => 'Temas', 'url' => '/#temas', 'ordem' => 3],
        ];

        foreach ($itens as $item) {
            MenuItem::query()->create([
                ...$item,
                'parent_id' => null,
                'target_blank' => false,
                'ativo' => true,
            ]);
        }
    }
}
