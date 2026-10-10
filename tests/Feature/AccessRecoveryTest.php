<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\AccessLanding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $permissions): User
    {
        $role = Role::create(['name' => 'Operator sintetis '.Role::count(), 'menu_permissions' => $permissions]);

        return User::factory()->create(['role_id' => $role->id, 'password' => 'SyntheticTestOnly123']);
    }

    public function test_login_without_dashboard_permission_lands_on_allowed_module(): void
    {
        $user = $this->user(['manajemen_sewa.data_sewa' => ['view']]);
        $this->post('/login', ['email' => $user->email, 'password' => 'SyntheticTestOnly123'])->assertRedirect('/sewas');
        $this->get('/sewas')->assertOk();
    }

    public function test_denied_html_page_redirects_to_accessible_module_without_loop(): void
    {
        $this->actingAs($this->user(['manajemen_sewa.data_sewa' => ['view']]));
        foreach (['/', '/keuangans', '/pembayarans/create'] as $url) {
            $this->get($url)->assertRedirect('/sewas');
        }
        $this->get('/sewas')->assertOk();
        $this->getJson('/keuangans')->assertForbidden();
    }

    public function test_role_without_any_view_permission_receives_terminal_403_with_recovery_actions(): void
    {
        $this->actingAs($this->user(['manajemen_sewa.data_sewa' => ['create']]));
        $this->get('/')->assertForbidden()->assertSee('Hubungi pengelola')->assertSee('/profil-akun', false)->assertSee('/logout', false);
        $this->get('/keuangans')->assertForbidden();
        $this->get('/profil-akun')->assertOk();
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_superadmin_login_keeps_intended_url_and_dashboard_access(): void
    {
        $user = User::factory()->create(['password' => 'SyntheticTestOnly123']);
        $this->withSession(['url.intended' => url('/penghunis')])->post('/login', ['email' => $user->email, 'password' => 'SyntheticTestOnly123'])->assertRedirect('/penghunis');
        $this->get('/')->assertOk();
    }

    public function test_each_view_permission_has_an_accessible_landing_page(): void
    {
        foreach (Role::menuKeys() as $menu) {
            $user = $this->user([$menu => ['view']]);
            $landing = app(AccessLanding::class)->routeName($user);
            $this->assertNotNull($landing, $menu);
            $this->actingAs($user)->get(route($landing))->assertOk();
        }
    }

    public function test_intended_denied_url_recovers_without_granting_permission(): void
    {
        $user = $this->user(['manajemen_sewa.data_sewa' => ['view']]);
        $this->withSession(['url.intended' => url('/keuangans')])->post('/login', ['email' => $user->email, 'password' => 'SyntheticTestOnly123'])->assertRedirect('/keuangans');
        $this->get('/keuangans')->assertRedirect('/sewas');
        $this->get('/sewas')->assertOk();
        $this->getJson('/keuangans')->assertForbidden();
    }

    public function test_create_only_role_login_can_reach_profile_without_a_dashboard_loop(): void
    {
        $user = $this->user(['manajemen_sewa.data_sewa' => ['create']]);
        $this->post('/login', ['email' => $user->email, 'password' => 'SyntheticTestOnly123'])->assertRedirect('/profil-akun');
        $this->get('/profil-akun')->assertOk();
        $this->getJson('/sewas')->assertForbidden();
    }
}
