<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@techsolutions.com.br'],
            [
                'name' => 'Maria Administradora',
                'email' => 'admin@techsolutions.com.br',
                'password' => Hash::make('password'),
                'role' => UserRole::ADMIN,
            ]
        );

        // Create editor user
        User::firstOrCreate(
            ['email' => 'editor@techsolutions.com.br'],
            [
                'name' => 'Carlos Editor',
                'email' => 'editor@techsolutions.com.br',
                'password' => Hash::make('password'),
                'role' => UserRole::EDITOR,
            ]
        );

        // Create additional editor for demonstration
        User::firstOrCreate(
            ['email' => 'financeiro@techsolutions.com.br'],
            [
                'name' => 'Ana Financeiro',
                'email' => 'financeiro@techsolutions.com.br',
                'password' => Hash::make('password'),
                'role' => UserRole::EDITOR,
            ]
        );
    }
}