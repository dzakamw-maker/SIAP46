<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Setting;
use App\Models\StaffAttendance;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    private function createAdminUser(): User
    {
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);

        return User::create([
            'role_id' => $adminRole->id,
            'full_name' => 'Guru Pembimbing',
            'username' => 'admin_att',
            'password' => 'secret123',
            'is_active' => true,
        ]);
    }

    private function createCashierUser(): User
    {
        $kasirRole = Role::firstOrCreate(['name' => 'Kasir']);

        return User::create([
            'role_id' => $kasirRole->id,
            'full_name' => 'Siswa Kasir',
            'username' => 'kasir_att',
            'class_group' => 'XI AKL 1',
            'password' => 'secret123',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_student_and_teacher_notes_on_attendance(): void
    {
        $admin = $this->createAdminUser();
        $cashier = $this->createCashierUser();

        $attendance = StaffAttendance::create([
            'attendance_date' => today(),
            'user_id' => $cashier->id,
            'student_note' => 'Typo shift pgi',
            'teacher_note' => null,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.attendance.update', $attendance->id), [
            'student_note' => 'Shift pagi lancar',
            'teacher_note' => 'Sudah dicek guru, rapi.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $attendance->refresh();
        $this->assertEquals('Shift pagi lancar', $attendance->student_note);
        $this->assertEquals('Sudah dicek guru, rapi.', $attendance->teacher_note);
    }

    public function test_non_admin_cannot_update_attendance_notes(): void
    {
        $cashier = $this->createCashierUser();

        $attendance = StaffAttendance::create([
            'attendance_date' => today(),
            'user_id' => $cashier->id,
            'student_note' => 'Catatan awal',
        ]);

        $response = $this->actingAs($cashier)->put(route('admin.attendance.update', $attendance->id), [
            'student_note' => 'Diubah sendiri',
            'teacher_note' => 'Diisi sendiri',
        ]);

        $response->assertStatus(403);
        $attendance->refresh();
        $this->assertEquals('Catatan awal', $attendance->student_note);
        $this->assertNull($attendance->teacher_note);
    }

    public function test_admin_can_update_stamp_selling_and_cost_prices(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post(route('admin.settings.stamp-prices'), [
            'stamp_selling_price' => 12000,
            'stamp_cost_price' => 10500,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(12000, Setting::getInt(Setting::STAMP_SELLING_PRICE, 11000));
        $this->assertEquals(10500, Setting::getInt(Setting::STAMP_COST_PRICE, 10000));

        // Pastikan halaman EOD admin dan kasir serta landing page menampilkan harga baru
        $this->actingAs($admin)->get(route('admin.eod'))
            ->assertStatus(200)
            ->assertSee('value="12000"', false)
            ->assertSee('value="10500"', false);

        $this->actingAs($admin)->get(route('kasir.eod'))
            ->assertStatus(200)
            ->assertSee('10.500');

        auth()->logout();
        $this->get(route('home'))
            ->assertStatus(200)
            ->assertSee('12.000');
    }
}
