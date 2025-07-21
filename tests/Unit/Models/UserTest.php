<?php

namespace Tests\Unit\Models;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created_with_valid_data()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => UserRole::ADMIN,
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals(UserRole::ADMIN, $user->role);
    }

    public function test_user_role_is_cast_to_enum()
    {
        $user = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);

        $this->assertInstanceOf(UserRole::class, $user->role);
        $this->assertEquals(UserRole::SUPER_ADMIN, $user->role);
    }

    public function test_user_password_is_hashed()
    {
        $user = User::factory()->create(['password' => 'plaintext']);

        $this->assertNotEquals('plaintext', $user->password);
        $this->assertTrue(password_verify('plaintext', $user->password));
    }

    public function test_user_email_must_be_unique()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->create(['email' => 'test@example.com']);
    }

    public function test_user_has_fillable_attributes()
    {
        $user = new User();
        $fillable = $user->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
        $this->assertContains('role', $fillable);
    }

    public function test_user_has_hidden_attributes()
    {
        $user = new User();
        $hidden = $user->getHidden();

        $this->assertContains('password', $hidden);
        $this->assertContains('remember_token', $hidden);
    }

    public function test_user_default_role_is_editor()
    {
        $user = User::factory()->create();

        $this->assertEquals(UserRole::EDITOR, $user->role);
    }

    public function test_user_can_have_super_admin_role()
    {
        $user = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);

        $this->assertEquals(UserRole::SUPER_ADMIN, $user->role);
        $this->assertEquals('Super Admin', $user->role->label());
    }

    public function test_user_can_have_admin_role()
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->assertEquals(UserRole::ADMIN, $user->role);
        $this->assertEquals('Admin', $user->role->label());
    }

    public function test_user_can_have_editor_role()
    {
        $user = User::factory()->create(['role' => UserRole::EDITOR]);

        $this->assertEquals(UserRole::EDITOR, $user->role);
        $this->assertEquals('Editor', $user->role->label());
    }
}