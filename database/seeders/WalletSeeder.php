<?php

namespace Database\Seeders;

use App\Models\Wallet;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wallets = [
            [
                'name' => 'Despesas Operacionais',
                'description' => 'Gastos relacionados ao funcionamento diário da empresa',
                'budget' => 5000.00
            ],
            [
                'name' => 'Receitas de Vendas',
                'description' => 'Receitas provenientes da venda de produtos e serviços',
                'budget' => 10000.00
            ],
            [
                'name' => 'Investimentos',
                'description' => 'Gastos com equipamentos, tecnologia e melhorias',
                'budget' => 15000.00
            ],
            [
                'name' => 'Marketing e Publicidade',
                'description' => 'Investimentos em marketing digital e campanhas publicitárias',
                'budget' => 3000.00
            ],
            [
                'name' => 'Recursos Humanos',
                'description' => 'Salários, benefícios e treinamentos dos funcionários',
                'budget' => 8000.00
            ]
        ];

        foreach ($wallets as $walletData) {
            Wallet::firstOrCreate(
                ['name' => $walletData['name']],
                $walletData
            );
        }
    }
}