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
                'description' => 'Gastos relacionados ao funcionamento diário da empresa'
            ],
            [
                'name' => 'Receitas de Vendas',
                'description' => 'Receitas provenientes da venda de produtos e serviços'
            ],
            [
                'name' => 'Investimentos',
                'description' => 'Gastos com equipamentos, tecnologia e melhorias'
            ],
            [
                'name' => 'Marketing e Publicidade',
                'description' => 'Investimentos em marketing digital e campanhas publicitárias'
            ],
            [
                'name' => 'Recursos Humanos',
                'description' => 'Salários, benefícios e treinamentos dos funcionários'
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