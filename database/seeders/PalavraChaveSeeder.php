<?php

namespace Database\Seeders;

use App\Models\PalavraChave;
use Illuminate\Database\Seeder;

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
            'Culinária',
            'Receita',
            'Música',
            'Dança',
            'Esporte',
            'Hobby',
            'Fotografia',
            'Carta',
            'Diário',
            'Aniversário',
            'Natal',
            'Ano Novo',
            'Festa',
            'Saudade',
            'Nostalgia',
            'Luto',
            'Perda',
            'Esperança',
            'Conquista',
            'Desafio',
            'Aventura',
            'Natureza',
            'Praia',
            'Campo',
            'Raízes',
            'Comunidade',
            'Vizinhança',
            'Infância feliz',
            'Primeiro emprego',
            'Primeiro amor',
        ];

        foreach ($nomes as $nome) {
            PalavraChave::query()->firstOrCreate(
                ['nome' => $nome],
            );
        }
    }
}
