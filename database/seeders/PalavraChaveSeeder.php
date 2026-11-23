<?php

namespace Database\Seeders;

use App\Models\PalavraChave;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PalavraChaveSeeder extends Seeder
{
    public function run(): void
    {
        $nomes = [
            'Família',
            'Infância',
            'Adolescência',
            'Juventude',
            'Amizade',
            'Amor',
            'Casamento',
            'Maternidade',
            'Paternidade',
            'Avós',
            'Netos',
            'Irmãos',
            'Viagem',
            'Férias',
            'Escola',
            'Faculdade',
            'Trabalho',
            'Carreira',
            'Aposentadoria',
            'Casa',
            'Cidade natal',
            'Mudança',
            'Imigração',
            'Guerra',
            'Paz',
            'Fé',
            'Tradição',
            'Festa',
            'Aniversário',
            'Natal',
            'Ano Novo',
            'Culinária',
            'Receita',
            'Música',
            'Dança',
            'Esporte',
            'Hobby',
            'Natureza',
            'Campo',
            'Praia',
            'Saudade',
            'Nostalgia',
            'Conquista',
            'Desafio',
            'Perda',
            'Luto',
            'Esperança',
            'Gratidão',
            'Humor',
            'Aventura',
            'Fotografia',
            'Carta',
            'Diário',
            'Herança',
            'Raízes',
            'Comunidade',
            'Vizinhança',
            'Infância feliz',
            'Primeiro emprego',
            'Primeiro amor',
        ];

        foreach ($nomes as $nome) {
            PalavraChave::query()->updateOrCreate(
                ['identificador' => Str::slug($nome)],
                ['nome' => $nome],
            );
        }
    }
}
