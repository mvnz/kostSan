<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_never_creates_an_account_with_a_default_password(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_admin_can_be_created_with_a_hidden_strong_password(): void
    {
        $this->artisan('kost:admin-create', ['email' => 'operator@example.test', '--name' => 'Operator Uji'])
            ->expectsQuestion('Password (minimal 12 karakter, huruf dan angka)', 'Test-password-123')
            ->expectsQuestion('Ulangi password', 'Test-password-123')
            ->assertExitCode(0);
        $user = User::firstOrFail();
        $this->assertNull($user->role_id);
        $this->assertTrue(Hash::check('Test-password-123', $user->password));
    }

    public function test_command_rejects_weak_password_and_never_overwrites_an_account(): void
    {
        $this->artisan('kost:admin-create', ['email' => 'operator@example.test'])
            ->expectsQuestion('Password (minimal 12 karakter, huruf dan angka)', 'short1')
            ->expectsQuestion('Ulangi password', 'short1')
            ->assertExitCode(1);
        $this->assertDatabaseCount('users', 0);
        $user = User::factory()->create(['email' => 'operator@example.test']);
        $hash = $user->password;
        $this->artisan('kost:admin-create', ['email' => $user->email])->assertExitCode(1);
        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_noninteractive_admin_creation_fails_without_creating_credentials(): void
    {
        $this->artisan('kost:admin-create', ['email' => 'operator@example.test', '--no-interaction' => true])->assertExitCode(1);
        $this->assertDatabaseCount('users', 0);
    }
}
