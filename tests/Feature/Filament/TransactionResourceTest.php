<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->wallet = Wallet::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_render_transaction_list_page()
    {
        // Test the Livewire component directly instead of HTTP route
        Livewire::test(TransactionResource\Pages\ListTransactions::class)
            ->assertSuccessful();
    }

    public function test_can_list_transactions()
    {
        $transactions = Transaction::factory()->count(3)->create([
            'wallet_id' => $this->wallet->id,
        ]);

        Livewire::test(TransactionResource\Pages\ListTransactions::class)
            ->assertCanSeeTableRecords($transactions);
    }

    public function test_can_render_transaction_create_page()
    {
        Livewire::test(TransactionResource\Pages\CreateTransaction::class)
            ->assertSuccessful();
    }

    public function test_can_create_transaction()
    {
        $newData = [
            'item' => 'New Transaction',
            'date' => '2024-01-20',
            'quantity' => 2.50,
            'value' => 150.75,
            'wallet_id' => $this->wallet->id,
        ];

        Livewire::test(TransactionResource\Pages\CreateTransaction::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('transactions', [
            'item' => 'New Transaction',
            'quantity' => 2.50,
            'value' => 150.75,
            'wallet_id' => $this->wallet->id,
        ]);
    }

    public function test_can_validate_transaction_creation()
    {
        Livewire::test(TransactionResource\Pages\CreateTransaction::class)
            ->fillForm([
                'item' => '',
                'date' => '',
                'quantity' => -1,
                'value' => '',
                'wallet_id' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'item' => 'required',
                'date' => 'required',
                'quantity' => 'min',
                'value' => 'required',
                'wallet_id' => 'required',
            ]);
    }

    public function test_can_render_transaction_edit_page()
    {
        $transaction = Transaction::factory()->create(['wallet_id' => $this->wallet->id]);

        Livewire::test(TransactionResource\Pages\EditTransaction::class, [
            'record' => $transaction->getRouteKey(),
        ])
            ->assertSuccessful();
    }

    public function test_can_retrieve_transaction_data()
    {
        $transaction = Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'item' => 'Test Transaction',
            'quantity' => 1.50,
            'value' => 100.00,
        ]);

        Livewire::test(TransactionResource\Pages\EditTransaction::class, [
            'record' => $transaction->getRouteKey(),
        ])
            ->assertFormSet([
                'item' => 'Test Transaction',
                'quantity' => 1.50,
                'value' => 100.00,
                'wallet_id' => $this->wallet->id,
            ]);
    }

    public function test_can_update_transaction()
    {
        $transaction = Transaction::factory()->create(['wallet_id' => $this->wallet->id]);

        $newData = [
            'item' => 'Updated Transaction',
            'date' => '2024-01-25',
            'quantity' => 3.00,
            'value' => 200.00,
            'wallet_id' => $this->wallet->id,
        ];

        Livewire::test(TransactionResource\Pages\EditTransaction::class, [
            'record' => $transaction->getRouteKey(),
        ])
            ->fillForm($newData)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'item' => 'Updated Transaction',
            'quantity' => 3.00,
            'value' => 200.00,
        ]);
    }

    public function test_can_delete_transaction()
    {
        $transaction = Transaction::factory()->create(['wallet_id' => $this->wallet->id]);

        Livewire::test(TransactionResource\Pages\ListTransactions::class)
            ->callTableAction('delete', $transaction);

        $this->assertModelMissing($transaction);
    }

    public function test_can_filter_transactions_by_wallet()
    {
        $wallet1 = Wallet::factory()->create(['name' => 'Wallet 1']);
        $wallet2 = Wallet::factory()->create(['name' => 'Wallet 2']);
        
        $transaction1 = Transaction::factory()->create(['wallet_id' => $wallet1->id]);
        $transaction2 = Transaction::factory()->create(['wallet_id' => $wallet2->id]);

        Livewire::test(TransactionResource\Pages\ListTransactions::class)
            ->filterTable('wallet_id', $wallet1->id)
            ->assertCanSeeTableRecords([$transaction1])
            ->assertCanNotSeeTableRecords([$transaction2]);
    }

    public function test_can_filter_transactions_by_date_range()
    {
        $transaction1 = Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'date' => '2024-01-15',
        ]);
        $transaction2 = Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'date' => '2024-02-15',
        ]);

        Livewire::test(TransactionResource\Pages\ListTransactions::class)
            ->filterTable('date_range', [
                'date_from' => '2024-01-01',
                'date_to' => '2024-01-31',
            ])
            ->assertCanSeeTableRecords([$transaction1])
            ->assertCanNotSeeTableRecords([$transaction2]);
    }

    public function test_can_search_transactions()
    {
        $transaction1 = Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'item' => 'Searchable Item',
        ]);
        $transaction2 = Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'item' => 'Other Item',
        ]);

        Livewire::test(TransactionResource\Pages\ListTransactions::class)
            ->searchTable('Searchable')
            ->assertCanSeeTableRecords([$transaction1])
            ->assertCanNotSeeTableRecords([$transaction2]);
    }

    public function test_can_sort_transactions()
    {
        $transaction1 = Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'date' => '2024-01-15',
        ]);
        $transaction2 = Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'date' => '2024-01-10',
        ]);

        Livewire::test(TransactionResource\Pages\ListTransactions::class)
            ->sortTable('date', 'desc')
            ->assertCanSeeTableRecords([$transaction1, $transaction2], inOrder: true);
    }

    public function test_transaction_form_has_wallet_relationship()
    {
        Livewire::test(TransactionResource\Pages\CreateTransaction::class)
            ->assertFormFieldExists('wallet_id');
    }

    public function test_can_create_wallet_from_transaction_form()
    {
        $walletData = [
            'name' => 'New Wallet from Form',
            'description' => 'Created from transaction form',
        ];

        Livewire::test(TransactionResource\Pages\CreateTransaction::class)
            ->fillForm([
                'item' => 'Test Transaction',
                'date' => '2024-01-20',
                'quantity' => 1.00,
                'value' => 100.00,
            ])
            ->callFormComponentAction('wallet_id', 'createOption', $walletData)
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('wallets', [
            'name' => 'New Wallet from Form',
            'description' => 'Created from transaction form',
        ]);
    }

    public function test_different_user_roles_can_access_transactions()
    {
        // Test SUPER_ADMIN access
        $superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $this->actingAs($superAdmin);
        
        Livewire::test(TransactionResource\Pages\ListTransactions::class)
            ->assertSuccessful();

        // Test ADMIN access
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($admin);
        
        Livewire::test(TransactionResource\Pages\ListTransactions::class)
            ->assertSuccessful();

        // Test EDITOR access
        $editor = User::factory()->create(['role' => UserRole::EDITOR]);
        $this->actingAs($editor);
        
        Livewire::test(TransactionResource\Pages\ListTransactions::class)
            ->assertSuccessful();
    }
}