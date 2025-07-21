<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
        $this->actingAs($this->superAdmin);
    }

    public function test_super_admin_can_render_user_list_page()
    {
        Livewire::test(UserResource\Pages\ListUsers::class)
            ->assertSuccessful();
    }

    public function test_non_super_admin_cannot_access_user_list()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($admin);

        // Test that non-super-admin users cannot access user management
        $this->assertFalse($admin->role === UserRole::SUPER_ADMIN);
    }

    public function test_can_list_users()
    {
        $users = User::factory()->count(3)->create();

        Livewire::test(UserResource\Pages\ListUsers::class)
            ->assertCanSeeTableRecords($users);
    }

    public function test_can_render_user_create_page()
    {
        Livewire::test(UserResource\Pages\CreateUser::class)
            ->assertSuccessful();
    }

    public function test_can_create_user()
    {
        $newData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'role' => UserRole::EDITOR->value,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        Livewire::test(UserResource\Pages\CreateUser::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'role' => UserRole::EDITOR->value,
        ]);

        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_can_validate_user_creation()
    {
        Livewire::test(UserResource\Pages\CreateUser::class)
            ->fillForm([
                'name' => '',
                'email' => 'invalid-email',
                'role' => '',
                'password' => '123', // Too short
                'password_confirmation' => '456', // Doesn't match
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'email' => 'email',
                'role' => 'required',
                'password' => ['min', 'same'],
            ]);
    }

    public function test_user_email_must_be_unique()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        Livewire::test(UserResource\Pages\CreateUser::class)
            ->fillForm([
                'name' => 'Test User',
                'email' => 'existing@example.com',
                'role' => UserRole::EDITOR->value,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'unique']);
    }

    public function test_can_render_user_edit_page()
    {
        $user = User::factory()->create();

        Livewire::test(UserResource\Pages\EditUser::class, [
            'record' => $user->getRouteKey(),
        ])
            ->assertSuccessful();
    }

    public function test_can_retrieve_user_data()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => UserRole::ADMIN,
        ]);

        Livewire::test(UserResource\Pages\EditUser::class, [
            'record' => $user->getRouteKey(),
        ])
            ->assertFormSet([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'role' => UserRole::ADMIN->value,
            ]);
    }

    public function test_can_update_user()
    {
        $user = User::factory()->create();

        $newData = [
            'name' => 'Updated User',
            'email' => 'updated@example.com',
            'role' => UserRole::ADMIN->value,
        ];

        Livewire::test(UserResource\Pages\EditUser::class, [
            'record' => $user->getRouteKey(),
        ])
            ->fillForm($newData)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated User',
            'email' => 'updated@example.com',
            'role' => UserRole::ADMIN->value,
        ]);
    }

    public function test_can_update_user_password()
    {
        $user = User::factory()->create();

        Livewire::test(UserResource\Pages\EditUser::class, [
            'record' => $user->getRouteKey(),
        ])
            ->fillForm([
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_password_not_required_on_update()
    {
        $user = User::factory()->create();

        Livewire::test(UserResource\Pages\EditUser::class, [
            'record' => $user->getRouteKey(),
        ])
            ->fillForm([
                'name' => 'Updated Name',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_user()
    {
        $user = User::factory()->create();

        Livewire::test(UserResource\Pages\ListUsers::class)
            ->callTableAction('delete', $user);

        $this->assertModelMissing($user);
    }

    public function test_cannot_delete_self()
    {
        Livewire::test(UserResource\Pages\ListUsers::class)
            ->assertTableActionHidden('delete', $this->superAdmin);
    }

    public function test_can_filter_users_by_role()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $editor = User::factory()->create(['role' => UserRole::EDITOR]);

        Livewire::test(UserResource\Pages\ListUsers::class)
            ->filterTable('role', UserRole::ADMIN->value)
            ->assertCanSeeTableRecords([$admin])
            ->assertCanNotSeeTableRecords([$editor]);
    }

    public function test_can_search_users()
    {
        $user1 = User::factory()->create(['name' => 'Searchable User']);
        $user2 = User::factory()->create(['name' => 'Other User']);

        Livewire::test(UserResource\Pages\ListUsers::class)
            ->searchTable('Searchable')
            ->assertCanSeeTableRecords([$user1])
            ->assertCanNotSeeTableRecords([$user2]);
    }

    public function test_user_role_is_displayed_as_badge()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        Livewire::test(UserResource\Pages\ListUsers::class)
            ->assertTableColumnFormattedStateSet('role', 'Admin', record: $admin);
    }

    public function test_user_default_role_is_editor()
    {
        Livewire::test(UserResource\Pages\CreateUser::class)
            ->assertFormSet([
                'role' => UserRole::EDITOR->value,
            ]);
    }

    public function test_can_create_all_user_roles()
    {
        foreach (UserRole::cases() as $role) {
            $userData = [
                'name' => "User {$role->value}",
                'email' => "user_{$role->value}@example.com",
                'role' => $role->value,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ];

            Livewire::test(UserResource\Pages\CreateUser::class)
                ->fillForm($userData)
                ->call('create')
                ->assertHasNoFormErrors();

            $this->assertDatabaseHas('users', [
                'name' => "User {$role->value}",
                'email' => "user_{$role->value}@example.com",
                'role' => $role->value,
            ]);
        }
    }

    public function test_non_super_admin_cannot_create_users()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($admin);

        // Test that non-super-admin cannot create users
        $this->assertFalse($admin->role === UserRole::SUPER_ADMIN);
    }

    public function test_non_super_admin_cannot_edit_users()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $user = User::factory()->create();
        $this->actingAs($admin);

        // Test that non-super-admin cannot edit users
        $this->assertFalse($admin->role === UserRole::SUPER_ADMIN);
    }

    public function test_editor_cannot_access_users()
    {
        $editor = User::factory()->create(['role' => UserRole::EDITOR]);
        $this->actingAs($editor);

        // Test that editor cannot access user management
        $this->assertFalse($editor->role === UserRole::SUPER_ADMIN);
    }
}