<?php

namespace Database\Seeders;

use App\Enums\StatusUsuario;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            [
                'email' => 'pedrohenrique1207ph@gmail.com',
                'nome' => 'Pedro Henrique',
                'nickname' => 'pedrohenrique',
                'password' => '123456',
                'email_verified_at' => now(),
                'status' => StatusUsuario::Ativo,
                'ultimo_acesso_em' => now()->subHours(2),
            ],
            [
                'email' => 'ana.souza@example.com',
                'nome' => 'Ana Souza',
                'nickname' => 'anasouza',
                'password' => 'password',
                'email_verified_at' => now(),
                'status' => StatusUsuario::Ativo,
                'ultimo_acesso_em' => now()->subDays(1),
            ],
            [
                'email' => 'bruno.lima@example.com',
                'nome' => 'Bruno Lima',
                'nickname' => 'brunolima',
                'password' => 'password',
                'email_verified_at' => now(),
                'status' => StatusUsuario::Ativo,
                'ultimo_acesso_em' => now()->subDays(3),
            ],
            [
                'email' => 'carla.mendes@example.com',
                'nome' => 'Carla Mendes',
                'nickname' => 'carlamendes',
                'password' => 'password',
                'email_verified_at' => now(),
                'status' => StatusUsuario::Desativado,
                'ultimo_acesso_em' => now()->subWeeks(2),
            ],
            [
                'email' => 'diego.ferreira@example.com',
                'nome' => 'Diego Ferreira',
                'nickname' => 'diegoferreira',
                'password' => 'password',
                'email_verified_at' => null,
                'status' => StatusUsuario::Ativo,
                'ultimo_acesso_em' => null,
            ],
            [
                'email' => 'elena.castro@example.com',
                'nome' => 'Elena Castro',
                'nickname' => 'elenacastro',
                'password' => 'password',
                'email_verified_at' => now(),
                'status' => StatusUsuario::Ativo,
                'ultimo_acesso_em' => now()->subHours(8),
            ],
            [
                'email' => 'felipe.ramos@example.com',
                'nome' => 'Felipe Ramos',
                'nickname' => 'feliperamos',
                'password' => 'password',
                'email_verified_at' => now(),
                'status' => StatusUsuario::Desativado,
                'ultimo_acesso_em' => now()->subMonth(),
            ],
            [
                'email' => 'gabriela.nunes@example.com',
                'nome' => 'Gabriela Nunes',
                'nickname' => 'gabrielanunes',
                'password' => 'password',
                'email_verified_at' => null,
                'status' => StatusUsuario::Ativo,
                'ultimo_acesso_em' => now()->subDays(5),
            ],
            [
                'email' => 'henrique.alves@example.com',
                'nome' => 'Henrique Alves',
                'nickname' => 'henriquealves',
                'password' => 'password',
                'email_verified_at' => now(),
                'status' => StatusUsuario::Ativo,
                'ultimo_acesso_em' => now()->subMinutes(40),
            ],
            [
                'email' => 'isabela.torres@example.com',
                'nome' => 'Isabela Torres',
                'nickname' => 'isabelatorres',
                'password' => 'password',
                'email_verified_at' => now(),
                'status' => StatusUsuario::Ativo,
                'ultimo_acesso_em' => now()->subDays(10),
            ],
            [
                'email' => 'joao.pereira@example.com',
                'nome' => 'João Pereira',
                'nickname' => 'joaopereira',
                'password' => 'password',
                'email_verified_at' => now(),
                'status' => StatusUsuario::Desativado,
                'ultimo_acesso_em' => null,
            ],
            [
                'email' => 'larissa.mota@example.com',
                'nome' => 'Larissa Mota',
                'nickname' => 'larissamota',
                'password' => 'password',
                'email_verified_at' => null,
                'status' => StatusUsuario::Ativo,
                'ultimo_acesso_em' => now()->subHours(12),
            ],
        ];

        foreach ($usuarios as $usuario) {
            User::query()->updateOrCreate(
                ['email' => $usuario['email']],
                $usuario,
            );
        }
    }
}
