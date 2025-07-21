<?php

namespace Tests\Unit\Models;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_can_be_created_with_valid_data()
    {
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->create([
            'item' => 'Test Item',
            'date' => '2024-01-15',
            'quantity' => 2.50,
            'value' => 150.75,
            'wallet_id' => $wallet->id,
        ]);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals('Test Item', $transaction->item);
        $this->assertEquals('2024-01-15', $transaction->date->format('Y-m-d'));
        $this->assertEquals(2.50, $transaction->quantity);
        $this->assertEquals(150.75, $transaction->value);
        $this->assertEquals($wallet->id, $transaction->wallet_id);
    }

    public function test_transaction_has_fillable_attributes()
    {
        $transaction = new Transaction();
        $fillable = $transaction->getFillable();

        $this->assertContains('item', $fillable);
        $this->assertContains('date', $fillable);
        $this->assertContains('quantity', $fillable);
        $this->assertContains('value', $fillable);
        $this->assertContains('wallet_id', $fillable);
    }

    public function test_transaction_date_is_cast_to_date()
    {
        $transaction = Transaction::factory()->create(['date' => '2024-01-15']);

        $this->assertInstanceOf(\Carbon\Carbon::class, $transaction->date);
        $this->assertEquals('2024-01-15', $transaction->date->format('Y-m-d'));
    }

    public function test_transaction_quantity_is_cast_to_decimal()
    {
        $transaction = Transaction::factory()->create(['quantity' => 10.5]);

        $this->assertEquals('10.50', $transaction->quantity);
    }

    public function test_transaction_value_is_cast_to_decimal()
    {
        $transaction = Transaction::factory()->create(['value' => 100.5]);

        $this->assertEquals('100.50', $transaction->value);
    }

    public function test_transaction_belongs_to_wallet()
    {
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->create(['wallet_id' => $wallet->id]);

        $this->assertInstanceOf(Wallet::class, $transaction->wallet);
        $this->assertEquals($wallet->id, $transaction->wallet->id);
        $this->assertEquals($wallet->name, $transaction->wallet->name);
    }

    public function test_transaction_requires_wallet_id()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Transaction::create([
            'item' => 'Test Item',
            'date' => '2024-01-15',
            'quantity' => 1.00,
            'value' => 100.00,
            // wallet_id is missing
        ]);
    }

    public function test_transaction_wallet_id_must_exist()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Transaction::create([
            'item' => 'Test Item',
            'date' => '2024-01-15',
            'quantity' => 1.00,
            'value' => 100.00,
            'wallet_id' => 999, // Non-existent wallet
        ]);
    }

    public function test_transaction_can_have_negative_value()
    {
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->create([
            'value' => -50.00,
            'wallet_id' => $wallet->id,
        ]);

        $this->assertEquals('-50.00', $transaction->value);
    }

    public function test_transaction_can_have_zero_quantity()
    {
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->create([
            'quantity' => 0.00,
            'wallet_id' => $wallet->id,
        ]);

        $this->assertEquals('0.00', $transaction->quantity);
    }

    public function test_transaction_handles_decimal_precision()
    {
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->create([
            'quantity' => 1.234,
            'value' => 99.999,
            'wallet_id' => $wallet->id,
        ]);

        // Should be rounded to 2 decimal places
        $this->assertEquals('1.23', $transaction->quantity);
        $this->assertEquals('100.00', $transaction->value);
    }
}