<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->wallet = Wallet::factory()->create();
    }

    private function authenticateUser(): void
    {
        Sanctum::actingAs($this->user);
    }

    public function test_unauthenticated_user_cannot_access_api()
    {
        $response = $this->getJson('/api/transactions');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_transactions()
    {
        $this->authenticateUser();
        
        $transaction1 = Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'item' => 'Test Item 1',
            'date' => '2024-01-15',
        ]);
        $transaction2 = Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'item' => 'Test Item 2',
            'date' => '2024-01-10',
        ]);

        $response = $this->getJson('/api/transactions');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Transações recuperadas com sucesso'
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'item',
                        'date',
                        'quantity',
                        'value',
                        'wallet_id',
                        'wallet' => [
                            'id',
                            'name',
                            'description'
                        ]
                    ]
                ],
                'message'
            ]);

        // Should be ordered by date desc (newest first)
        $responseData = $response->json('data');
        $this->assertEquals($transaction1->id, $responseData[0]['id']);
        $this->assertEquals($transaction2->id, $responseData[1]['id']);
    }

    public function test_authenticated_user_can_create_transaction()
    {
        $this->authenticateUser();

        $transactionData = [
            'item' => 'New Transaction',
            'date' => '2024-01-20',
            'quantity' => 2.50,
            'value' => 150.75,
            'wallet_id' => $this->wallet->id,
        ];

        $response = $this->postJson('/api/transactions', $transactionData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Transação criada com sucesso'
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'item',
                    'date',
                    'quantity',
                    'value',
                    'wallet_id',
                    'wallet'
                ],
                'message'
            ]);

        $this->assertDatabaseHas('transactions', [
            'item' => 'New Transaction',
            'quantity' => 2.50,
            'value' => 150.75,
            'wallet_id' => $this->wallet->id,
        ]);
        
        // Check date separately since it's stored with timestamp
        $transaction = \App\Models\Transaction::where('item', 'New Transaction')->first();
        $this->assertEquals('2024-01-20', $transaction->date->format('Y-m-d'));
    }

    public function test_create_transaction_validation_fails_with_missing_required_fields()
    {
        $this->authenticateUser();

        $response = $this->postJson('/api/transactions', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Dados de validação falharam'
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => [
                    'item',
                    'date',
                    'quantity',
                    'value',
                    'wallet_id'
                ]
            ]);
    }

    public function test_create_transaction_validation_fails_with_invalid_data()
    {
        $this->authenticateUser();

        $invalidData = [
            'item' => '', // empty string
            'date' => 'invalid-date',
            'quantity' => -1, // negative quantity
            'value' => 'not-a-number',
            'wallet_id' => 999, // non-existent wallet
        ];

        $response = $this->postJson('/api/transactions', $invalidData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Dados de validação falharam'
            ]);
    }

    public function test_create_transaction_validation_passes_with_valid_data()
    {
        $this->authenticateUser();

        $validData = [
            'item' => 'Valid Transaction',
            'date' => '2024-01-20',
            'quantity' => 1.00,
            'value' => 100.00,
            'wallet_id' => $this->wallet->id,
        ];

        $response = $this->postJson('/api/transactions', $validData);

        $response->assertStatus(201);
    }

    public function test_authenticated_user_can_show_specific_transaction()
    {
        $this->authenticateUser();

        $transaction = Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'item' => 'Specific Transaction',
        ]);

        $response = $this->getJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Transação recuperada com sucesso',
                'data' => [
                    'id' => $transaction->id,
                    'item' => 'Specific Transaction',
                    'wallet_id' => $this->wallet->id,
                ]
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'item',
                    'date',
                    'quantity',
                    'value',
                    'wallet_id',
                    'wallet'
                ],
                'message'
            ]);
    }

    public function test_show_nonexistent_transaction_returns_404()
    {
        $this->authenticateUser();

        $response = $this->getJson('/api/transactions/999');

        $response->assertStatus(404);
    }

    public function test_authenticated_user_can_update_transaction()
    {
        $this->authenticateUser();

        $transaction = Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'item' => 'Original Item',
        ]);

        $updateData = [
            'item' => 'Updated Item',
            'date' => '2024-01-25',
            'quantity' => 3.00,
            'value' => 200.00,
            'wallet_id' => $this->wallet->id,
        ];

        $response = $this->putJson("/api/transactions/{$transaction->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Transação atualizada com sucesso',
                'data' => [
                    'id' => $transaction->id,
                    'item' => 'Updated Item',
                ]
            ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'item' => 'Updated Item',
            'quantity' => 3.00,
            'value' => 200.00,
        ]);
        
        // Check date separately since it's stored with timestamp
        $updatedTransaction = \App\Models\Transaction::find($transaction->id);
        $this->assertEquals('2024-01-25', $updatedTransaction->date->format('Y-m-d'));
    }

    public function test_update_transaction_validation_fails_with_invalid_data()
    {
        $this->authenticateUser();

        $transaction = Transaction::factory()->create(['wallet_id' => $this->wallet->id]);

        $invalidData = [
            'item' => '',
            'date' => 'invalid-date',
            'quantity' => -1,
            'value' => 'not-a-number',
            'wallet_id' => 999,
        ];

        $response = $this->putJson("/api/transactions/{$transaction->id}", $invalidData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Dados de validação falharam'
            ]);
    }

    public function test_update_nonexistent_transaction_returns_404()
    {
        $this->authenticateUser();

        $updateData = [
            'item' => 'Updated Item',
            'date' => '2024-01-25',
            'quantity' => 1.00,
            'value' => 100.00,
            'wallet_id' => $this->wallet->id,
        ];

        $response = $this->putJson('/api/transactions/999', $updateData);

        $response->assertStatus(404);
    }

    public function test_authenticated_user_can_delete_transaction()
    {
        $this->authenticateUser();

        $transaction = Transaction::factory()->create(['wallet_id' => $this->wallet->id]);

        $response = $this->deleteJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Transação excluída com sucesso'
            ]);

        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id,
        ]);
    }

    public function test_delete_nonexistent_transaction_returns_404()
    {
        $this->authenticateUser();

        $response = $this->deleteJson('/api/transactions/999');

        $response->assertStatus(404);
    }

    public function test_api_returns_transactions_with_wallet_relationship()
    {
        $this->authenticateUser();

        $wallet = Wallet::factory()->create(['name' => 'Test Wallet']);
        $transaction = Transaction::factory()->create([
            'wallet_id' => $wallet->id,
            'item' => 'Test Transaction',
        ]);

        $response = $this->getJson('/api/transactions');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.wallet.name', 'Test Wallet')
            ->assertJsonPath('data.0.wallet.id', $wallet->id);
    }

    public function test_api_handles_empty_transaction_list()
    {
        $this->authenticateUser();

        $response = $this->getJson('/api/transactions');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
                'message' => 'Transações recuperadas com sucesso'
            ]);
    }

    public function test_different_user_roles_can_access_api()
    {
        // Test with SUPER_ADMIN
        $superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        Sanctum::actingAs($superAdmin);
        
        $response = $this->getJson('/api/transactions');
        $response->assertStatus(200);

        // Test with ADMIN
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        Sanctum::actingAs($admin);
        
        $response = $this->getJson('/api/transactions');
        $response->assertStatus(200);

        // Test with EDITOR
        $editor = User::factory()->create(['role' => UserRole::EDITOR]);
        Sanctum::actingAs($editor);
        
        $response = $this->getJson('/api/transactions');
        $response->assertStatus(200);
    }

    public function test_api_rate_limiting_is_applied()
    {
        $this->authenticateUser();

        // This test verifies that rate limiting middleware is applied
        // We can't easily test the actual rate limiting without making many requests
        // But we can verify the middleware is configured by checking the response headers
        $response = $this->getJson('/api/transactions');

        $response->assertStatus(200);
        // Rate limiting headers should be present
        $this->assertNotNull($response->headers->get('X-RateLimit-Limit'));
    }

    public function test_api_returns_proper_json_structure_for_errors()
    {
        $this->authenticateUser();

        // Test validation error structure
        $response = $this->postJson('/api/transactions', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors'
            ])
            ->assertJson(['success' => false]);
    }

    public function test_transaction_values_are_properly_formatted()
    {
        $this->authenticateUser();

        $transaction = Transaction::factory()->create([
            'wallet_id' => $this->wallet->id,
            'quantity' => 1.234,
            'value' => 99.999,
        ]);

        $response = $this->getJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(200);
        
        $data = $response->json('data');
        // Values should be formatted to 2 decimal places
        $this->assertEquals('1.23', $data['quantity']);
        $this->assertEquals('100.00', $data['value']);
    }
}