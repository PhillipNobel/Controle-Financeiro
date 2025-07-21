<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update company data (singleton pattern)
        Company::updateOrCreate(
            ['id' => 1], // Assuming first company record
            [
                'name' => 'TechSolutions Ltda',
                'cnpj' => '12.345.678/0001-90',
                'razao_social' => 'TechSolutions Tecnologia e Consultoria Ltda',
                'inscricao_estadual' => '123.456.789.012',
                'telefone' => '(11) 99999-8888',
                'endereco' => 'Rua das Flores, 123, Centro, São Paulo - SP, CEP: 01234-567',
                'email' => 'contato@techsolutions.com.br',
                'pessoa_responsavel' => 'João Silva Santos',
                'website' => 'https://www.techsolutions.com.br'
            ]
        );
    }
}