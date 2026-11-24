<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategoriaSeeder::class,
            PalavraChaveSeeder::class,
            AreaTematicaSeeder::class,
            EtiquetaSeeder::class,
            BannerSeeder::class,
            MenuItemSeeder::class,
            ArtigoSeeder::class,
        ]);
    }
}
