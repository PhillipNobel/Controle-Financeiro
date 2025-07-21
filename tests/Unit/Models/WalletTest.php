<?php

namespace Tests\Unit\Models;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_can_be_created_with_valid_data()
    {
        $wallet = Wallet::factory()->create([
            'name' => 'Test Wallet',
            'description' => 'Test Description',
        ]);

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals('Test Wallet', $wallet->name);
        $this->assertEquals('Test Description', $wallet->description);
    }

    public function test_wallet_name_must_be_unique()
    {
        Wallet::factory()->create(['name' => 'Unique Wallet']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Wallet::factory()->create(['name' => 'Unique Wallet']);
    }

    public function test_wallet_has_fillable_attributes()
    {
        $wallet = new Wallet();
        $fillable = $wallet->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('description', $fillable);
    }

    public function test_wallet_has_many_transactions()
    {
        $wallet = Wallet::factory()->create();
        $transaction1 = Transaction::factory()->create(['wallet_id' => $wallet->id]);
        $transaction2 = Transaction::factory()->create(['wallet_id' => $wallet->id]);

        $this->assertCount(2, $wallet->transactions);
        $this->assertTrue($wallet->transactions->contains($transaction1));
        $this->assertTrue($wallet->transactions->contains($transaction2));
    }

    public function test_wallet_get_total_value_returns_zero_for_empty_wallet()
    {
        $wallet = Wallet::factory()->create();

        $this->assertEquals(0.0, $wallet->getTotalValue());
    }

    public function test_wallet_get_total_value_calculates_sum_correctly()
    {
        $wallet = Wallet::factory()->create();
        Transaction::factory()->create([
            'wallet_id' => $wallet->id,
            'value' => 100.50,
        ]);
        Transaction::factory()->create([
            'wallet_id' => $wallet->id,
            'value' => 250.75,
        ]);

        $this->assertEquals(351.25, $wallet->getTotalValue());
    }

    public function test_wallet_get_total_value_handles_negative_values()
    {
        $wallet = Wallet::factory()->create();
        Transaction::factory()->create([
            'wallet_id' => $wallet->id,
            'value' => 100.00,
        ]);
        Transaction::factory()->create([
            'wallet_id' => $wallet->id,
            'value' => -50.00,
        ]);

        $this->assertEquals(50.00, $wallet->getTotalValue());
    }

    public function test_wallet_description_can_be_null()
    {
        $wallet = Wallet::factory()->create(['description' => null]);

        $this->assertNull($wallet->description);
    }

    public function test_wallet_can_be_created_with_minimum_required_fields()
    {
        $wallet = Wallet::create(['name' => 'Minimal Wallet']);

        $this->assertEquals('Minimal Wallet', $wallet->name);
        $this->assertNull($wallet->description);
    }
}