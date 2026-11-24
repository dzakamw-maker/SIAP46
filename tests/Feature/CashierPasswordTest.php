<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CashierPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    private function createCashierUser(string $username = 'kasir123', string $password = 'initial123'): User
    {
        $kasirRole = Role::firstOrCreate(['name' => 'Kasir']);

        return User::create([
            'role_id' => $kasirRole->id,
            'full_name' => 'Budi Siswa Kasir',
            'username' => $username,
            'student_number' => '12345678',
            'class_group' => 'XI RPL 1',
            'password' => $password,
            'is_active' => true,
        ]);
    }

    public function test_cashier_can_view_password_change_page(): void
    {
        $cashier = $this->createCashierUser();

        $response = $this->actingAs($cashier)->get(route('kasir.password'));

        $response->assertStatus(200);
        $response->assertSee('Pengaturan Akun &amp; Ganti Password', false);
        $response->assertSee('Budi Siswa Kasir');
        $response->assertSee('kasir123');
        $response->assertSee('12345678');
        $response->assertSee('Username terkunci dan tidak dapat diubah.');
    }

    public function test_cashier_can_update_password_successfully(): void
    {
        $cashier = $this->createCashierUser('kasir123', 'oldpassword123');

        $response = $this->actingAs($cashier)->put(route('kasir.password.update'), [
            'password' => 'newsecret456',
            'password_confirmation' => 'newsecret456',
        ]);

        $response->assertRedirect(route('kasir.password'));
        $response->assertSessionHas('success', 'Password akun Anda berhasil diperbarui!');

        $cashier->refresh();
        $this->assertTrue(Hash::check('newsecret456', $cashier->password));
        $this->assertFalse(Hash::check('oldpassword123', $cashier->password));
    }

    public function test_password_update_requires_minimum_6_characters(): void
    {
        $cashier = $this->createCashierUser();

        $response = $this->actingAs($cashier)->put(route('kasir.password.update'), [
            'password' => '12345',
            'password_confirmation' => '12345',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_password_update_requires_matching_confirmation(): void
    {
        $cashier = $this->createCashierUser();

        $response = $this->actingAs($cashier)->put(route('kasir.password.update'), [
            'password' => 'newsecret456',
            'password_confirmation' => 'different456',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_cashier_cannot_change_username_via_password_form(): void
    {
        $cashier = $this->createCashierUser('original_username');

        $response = $this->actingAs($cashier)->put(route('kasir.password.update'), [
            'username' => 'hacked_username',
            'password' => 'newsecret456',
            'password_confirmation' => 'newsecret456',
        ]);

        $response->assertRedirect(route('kasir.password'));

        $cashier->refresh();
        $this->assertEquals('original_username', $cashier->username);
        $this->assertNotEquals('hacked_username', $cashier->username);
    }

    public function test_guest_cannot_access_cashier_password_page(): void
    {
        $response = $this->get(route('kasir.password'));
        $response->assertRedirect('/login');
    }
}
