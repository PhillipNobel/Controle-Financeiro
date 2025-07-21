<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get wallets
        $despesasOperacionais = Wallet::where('name', 'Despesas Operacionais')->first();
        $receitasVendas = Wallet::where('name', 'Receitas de Vendas')->first();
        $investimentos = Wallet::where('name', 'Investimentos')->first();
        $marketing = Wallet::where('name', 'Marketing e Publicidade')->first();
        $recursosHumanos = Wallet::where('name', 'Recursos Humanos')->first();

        // Sample transactions for demonstration
        $transactions = [
            // Receitas de Vendas
            [
                'item' => 'Venda de Produto A',
                'date' => Carbon::now()->subDays(1),
                'quantity' => 5.00,
                'value' => 2500.00,
                'wallet_id' => $receitasVendas->id
            ],
            [
                'item' => 'Prestação de Serviço Consultoria',
                'date' => Carbon::now()->subDays(3),
                'quantity' => 1.00,
                'value' => 1800.00,
                'wallet_id' => $receitasVendas->id
            ],
            [
                'item' => 'Venda de Produto B',
                'date' => Carbon::now()->subDays(5),
                'quantity' => 3.00,
                'value' => 1200.00,
                'wallet_id' => $receitasVendas->id
            ],
            
            // Despesas Operacionais
            [
                'item' => 'Conta de Energia Elétrica',
                'date' => Carbon::now()->subDays(2),
                'quantity' => 1.00,
                'value' => -450.00,
                'wallet_id' => $despesasOperacionais->id
            ],
            [
                'item' => 'Internet e Telefone',
                'date' => Carbon::now()->subDays(4),
                'quantity' => 1.00,
                'value' => -280.00,
                'wallet_id' => $despesasOperacionais->id
            ],
            [
                'item' => 'Material de Escritório',
                'date' => Carbon::now()->subDays(6),
                'quantity' => 1.00,
                'value' => -150.00,
                'wallet_id' => $despesasOperacionais->id
            ],
            [
                'item' => 'Aluguel do Escritório',
                'date' => Carbon::now()->subDays(7),
                'quantity' => 1.00,
                'value' => -1200.00,
                'wallet_id' => $despesasOperacionais->id
            ],
            
            // Investimentos
            [
                'item' => 'Notebook Dell Inspiron',
                'date' => Carbon::now()->subDays(10),
                'quantity' => 2.00,
                'value' => -3200.00,
                'wallet_id' => $investimentos->id
            ],
            [
                'item' => 'Software de Gestão',
                'date' => Carbon::now()->subDays(12),
                'quantity' => 1.00,
                'value' => -890.00,
                'wallet_id' => $investimentos->id
            ],
            
            // Marketing e Publicidade
            [
                'item' => 'Campanha Google Ads',
                'date' => Carbon::now()->subDays(8),
                'quantity' => 1.00,
                'value' => -650.00,
                'wallet_id' => $marketing->id
            ],
            [
                'item' => 'Design de Material Gráfico',
                'date' => Carbon::now()->subDays(14),
                'quantity' => 1.00,
                'value' => -400.00,
                'wallet_id' => $marketing->id
            ],
            
            // Recursos Humanos
            [
                'item' => 'Salário Desenvolvedor',
                'date' => Carbon::now()->subDays(15),
                'quantity' => 1.00,
                'value' => -4500.00,
                'wallet_id' => $recursosHumanos->id
            ],
            [
                'item' => 'Vale Alimentação',
                'date' => Carbon::now()->subDays(15),
                'quantity' => 1.00,
                'value' => -600.00,
                'wallet_id' => $recursosHumanos->id
            ],
            [
                'item' => 'Curso de Capacitação',
                'date' => Carbon::now()->subDays(20),
                'quantity' => 1.00,
                'value' => -350.00,
                'wallet_id' => $recursosHumanos->id
            ],
            
            // More recent transactions for better dashboard visualization
            [
                'item' => 'Venda de Produto C',
                'date' => Carbon::now(),
                'quantity' => 2.00,
                'value' => 800.00,
                'wallet_id' => $receitasVendas->id
            ],
            [
                'item' => 'Combustível',
                'date' => Carbon::now(),
                'quantity' => 1.00,
                'value' => -120.00,
                'wallet_id' => $despesasOperacionais->id
            ]
        ];

        foreach ($transactions as $transactionData) {
            Transaction::create($transactionData);
        }
    }
}