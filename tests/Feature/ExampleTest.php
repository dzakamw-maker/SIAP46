<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('SIAP46');
        $response->assertSee('Daftar Layanan');
        $response->assertSee('Login Petugas');
    }

    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Silakan masuk menggunakan akun Anda');
    }

    public function test_unauthenticated_user_redirected_to_login_when_accessing_admin(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_admin_visiting_home_sees_admin_dashboard(): void
    {
        $adminRole = Role::create(['name' => 'Admin']);
        $admin = User::create([
            'role_id' => $adminRole->id,
            'full_name' => 'Admin Test',
            'username' => 'admintest',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Total Transaksi Hari Ini');
    }

    public function test_authenticated_cashier_visiting_home_sees_cashier_dashboard(): void
    {
        $kasirRole = Role::create(['name' => 'Kasir']);
        $kasir = User::create([
            'role_id' => $kasirRole->id,
            'full_name' => 'Kasir Test',
            'username' => 'kasirtest',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $response = $this->actingAs($kasir)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Input Transaksi');
    }

    public function test_dark_mode_toggle_and_theme_script_render_across_layouts(): void
    {
        $guestHome = $this->get('/');
        $guestHome->assertStatus(200);
        $guestHome->assertSee('data-theme-toggle', false);
        $guestHome->assertSee('window.toggleTheme', false);
        $guestHome->assertSee('dark:bg-slate-950', false);

        $login = $this->get('/login');
        $login->assertStatus(200);
        $login->assertSee('data-theme-toggle', false);
        $login->assertSee('dark:bg-zinc-900', false);

        $adminRole = Role::create(['name' => 'Admin']);
        $admin = User::create([
            'role_id' => $adminRole->id,
            'full_name' => 'Admin Dark',
            'username' => 'admindark',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $adminResponse = $this->actingAs($admin)->get('/');
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('data-theme-toggle', false);
        $adminResponse->assertSee('dark:bg-zinc-900', false);

        $kasirRole = Role::create(['name' => 'Kasir']);
        $kasir = User::create([
            'role_id' => $kasirRole->id,
            'full_name' => 'Kasir Dark',
            'username' => 'kasirdark',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $kasirResponse = $this->actingAs($kasir)->get('/');
        $kasirResponse->assertStatus(200);
        $kasirResponse->assertSee('data-theme-toggle', false);
        $kasirResponse->assertSee('dark:bg-zinc-900', false);
    }

    public function test_collapsible_sidebar_controls_render_in_admin_and_cashier_layouts(): void
    {
        $guestHome = $this->get('/');
        $guestHome->assertStatus(200);
        $guestHome->assertDontSee('id="appSidebar"', false);
        $guestHome->assertDontSee('aria-controls="appSidebar"', false);

        $adminRole = Role::create(['name' => 'Admin']);
        $admin = User::create([
            'role_id' => $adminRole->id,
            'full_name' => 'Admin Sidebar',
            'username' => 'adminsidebar',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $adminResponse = $this->actingAs($admin)->get('/');
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('id="appSidebar"', false);
        $adminResponse->assertSee('aria-controls="appSidebar"', false);
        $adminResponse->assertSee('data-sidebar-backdrop', false);
        $adminResponse->assertSee('window.toggleSidebar', false);

        $kasirRole = Role::create(['name' => 'Kasir']);
        $kasir = User::create([
            'role_id' => $kasirRole->id,
            'full_name' => 'Kasir Sidebar',
            'username' => 'kasirsidebar',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $kasirResponse = $this->actingAs($kasir)->get('/');
        $kasirResponse->assertStatus(200);
        $kasirResponse->assertSee('id="appSidebar"', false);
        $kasirResponse->assertSee('aria-controls="appSidebar"', false);
        $kasirResponse->assertSee('data-sidebar-backdrop', false);
        $kasirResponse->assertSee('window.toggleSidebar', false);
    }
}
