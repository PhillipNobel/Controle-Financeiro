<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Company;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_everything(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->create(['wallet_id' => $wallet->id]);
        $company = Company::factory()->create();
        $otherUser = User::factory()->create(['role' => UserRole::EDITOR]);

        // Test Transaction permissions
        $this->assertTrue($superAdmin->can('viewAny', Transaction::class));
        $this->assertTrue($superAdmin->can('view', $transaction));
        $this->assertTrue($superAdmin->can('create', Transaction::class));
        $this->assertTrue($superAdmin->can('update', $transaction));
        $this->assertTrue($superAdmin->can('delete', $transaction));

        // Test Wallet permissions
        $this->assertTrue($superAdmin->can('viewAny', Wallet::class));
        $this->assertTrue($superAdmin->can('view', $wallet));
        $this->assertTrue($superAdmin->can('create', Wallet::class));
        $this->assertTrue($superAdmin->can('update', $wallet));
        $this->assertTrue($superAdmin->can('delete', $wallet));

        // Test User permissions
        $this->assertTrue($superAdmin->can('viewAny', User::class));
        $this->assertTrue($superAdmin->can('view', $otherUser));
        $this->assertTrue($superAdmin->can('create', User::class));
        $this->assertTrue($superAdmin->can('update', $otherUser));
        $this->assertTrue($superAdmin->can('delete', $otherUser));
        $this->assertFalse($superAdmin->can('delete', $superAdmin)); // Cannot delete themselves

        // Test Company permissions
        $this->assertTrue($superAdmin->can('viewAny', Company::class));
        $this->assertTrue($superAdmin->can('view', $company));
        $this->assertTrue($superAdmin->can('create', Company::class));
        $this->assertTrue($superAdmin->can('update', $company));
        $this->assertTrue($superAdmin->can('delete', $company));
    }

    public function test_admin_has_limited_permissions(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->create(['wallet_id' => $wallet->id]);
        $company = Company::factory()->create();
        $otherUser = User::factory()->create(['role' => UserRole::EDITOR]);

        // Test Transaction permissions
        $this->assertTrue($admin->can('viewAny', Transaction::class));
        $this->assertTrue($admin->can('view', $transaction));
        $this->assertTrue($admin->can('create', Transaction::class));
        $this->assertTrue($admin->can('update', $transaction));
        $this->assertTrue($admin->can('delete', $transaction));

        // Test Wallet permissions
        $this->assertTrue($admin->can('viewAny', Wallet::class));
        $this->assertTrue($admin->can('view', $wallet));
        $this->assertTrue($admin->can('create', Wallet::class));
        $this->assertTrue($admin->can('update', $wallet));
        $this->assertTrue($admin->can('delete', $wallet));

        // Test User permissions (admin cannot manage users)
        $this->assertFalse($admin->can('viewAny', User::class));
        $this->assertFalse($admin->can('view', $otherUser));
        $this->assertFalse($admin->can('create', User::class));
        $this->assertFalse($admin->can('update', $otherUser));
        $this->assertFalse($admin->can('delete', $otherUser));

        // Test Company permissions
        $this->assertTrue($admin->can('viewAny', Company::class));
        $this->assertTrue($admin->can('view', $company));
        $this->assertFalse($admin->can('create', Company::class));
        $this->assertTrue($admin->can('update', $company));
        $this->assertFalse($admin->can('delete', $company));
    }

    public function test_editor_has_minimal_permissions(): void
    {
        $editor = User::factory()->create(['role' => UserRole::EDITOR]);
        $wallet = Wallet::factory()->create();
        $transaction = Transaction::factory()->create(['wallet_id' => $wallet->id]);
        $company = Company::factory()->create();
        $otherUser = User::factory()->create(['role' => UserRole::ADMIN]);

        // Test Transaction permissions (editor can manage transactions)
        $this->assertTrue($editor->can('viewAny', Transaction::class));
        $this->assertTrue($editor->can('view', $transaction));
        $this->assertTrue($editor->can('create', Transaction::class));
        $this->assertTrue($editor->can('update', $transaction));
        $this->assertFalse($editor->can('delete', $transaction)); // Cannot delete

        // Test Wallet permissions (editor can only view wallets)
        $this->assertTrue($editor->can('viewAny', Wallet::class));
        $this->assertTrue($editor->can('view', $wallet));
        $this->assertFalse($editor->can('create', Wallet::class));
        $this->assertFalse($editor->can('update', $wallet));
        $this->assertFalse($editor->can('delete', $wallet));

        // Test User permissions (editor cannot manage users)
        $this->assertFalse($editor->can('viewAny', User::class));
        $this->assertFalse($editor->can('view', $otherUser));
        $this->assertFalse($editor->can('create', User::class));
        $this->assertFalse($editor->can('update', $otherUser));
        $this->assertFalse($editor->can('delete', $otherUser));

        // Test Company permissions (editor cannot access company settings)
        $this->assertFalse($editor->can('viewAny', Company::class));
        $this->assertFalse($editor->can('view', $company));
        $this->assertFalse($editor->can('create', Company::class));
        $this->assertFalse($editor->can('update', $company));
        $this->assertFalse($editor->can('delete', $company));
    }
}
