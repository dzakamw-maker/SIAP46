<?php

namespace Tests\Feature;

use App\Models\DailyRecap;
use App\Models\Role;
use App\Models\StaffAttendance;
use App\Models\TransactionType;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    private function createCashierUser(string $username = 'kasir_att'): User
    {
        $kasirRole = Role::firstOrCreate(['name' => 'Kasir']);

        return User::create([
            'role_id' => $kasirRole->id,
            'full_name' => 'Kasir Presensi',
            'username' => $username,
            'class_group' => 'XI RPL 1',
            'password' => 'secret123',
            'is_active' => true,
        ]);
    }

    public function test_cashier_can_record_and_update_daily_attendance(): void
    {
        $cashier = $this->createCashierUser();

        $response = $this->actingAs($cashier)->post(route('kasir.attendance.store'), [
            'student_note' => 'Hadir shift pagi, loket siap.',
        ]);

        $response->assertRedirect(route('kasir.dashboard'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('staff_attendances', [
            'user_id' => $cashier->id,
            'student_note' => 'Hadir shift pagi, loket siap.',
        ]);

        // Update catatan pada hari yang sama tidak membuat baris duplikat
        $updateResponse = $this->actingAs($cashier)->post(route('kasir.attendance.store'), [
            'student_note' => 'Shift pagi selesai tanpa kendala.',
        ]);

        $updateResponse->assertRedirect(route('kasir.dashboard'));
        $this->assertEquals(1, StaffAttendance::where('user_id', $cashier->id)->count());
        $this->assertDatabaseHas('staff_attendances', [
            'user_id' => $cashier->id,
            'student_note' => 'Shift pagi selesai tanpa kendala.',
        ]);
    }

    public function test_cashier_attendance_requires_student_note(): void
    {
        $cashier = $this->createCashierUser();

        $response = $this->actingAs($cashier)->post(route('kasir.attendance.store'), [
            'student_note' => '',
        ]);

        $response->assertSessionHasErrors(['student_note']);
        $this->assertEquals(0, StaffAttendance::count());
    }

    public function test_transaction_is_blocked_without_attendance_when_shift_gates_are_enforced(): void
    {
        config(['app.enforce_shift_gates' => true]);

        $cashier = $this->createCashierUser();
        $type = TransactionType::firstOrCreate(
            ['code' => 'setor_tunai_bni'],
            ['name' => 'Setor Tunai BNI', 'is_active' => true]
        );

        DailyRecap::create([
            'recap_date' => today(),
            'opening_bni_balance' => 5000000,
            'opening_cash_balance' => 500000,
            'bni_balance_remaining' => 5000000,
            'cash_amount' => 500000,
            'opened_by' => $cashier->id,
        ]);

        $blocked = $this->actingAs($cashier)->post(route('kasir.transactions.store'), [
            'transaction_type_id' => $type->id,
            'customer_identifier' => '0123456789',
            'customer_name' => 'Nasabah Test',
            'amount' => 100000,
            'admin_fee' => 2500,
        ]);

        $blocked->assertSessionHasErrors(['transaction_type_id']);
        $this->assertDatabaseCount('transactions', 0);

        // Setelah presensi, transaksi berhasil
        $this->actingAs($cashier)->post(route('kasir.attendance.store'), [
            'student_note' => 'Sudah absen pagi',
        ]);

        $allowed = $this->actingAs($cashier)->post(route('kasir.transactions.store'), [
            'transaction_type_id' => $type->id,
            'customer_identifier' => '0123456789',
            'customer_name' => 'Nasabah Test',
            'amount' => 100000,
            'admin_fee' => 2500,
        ]);

        $allowed->assertRedirect(route('kasir.dashboard'));
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_dashboard_shows_attendance_prompt_before_clock_in_and_note_after(): void
    {
        $cashier = $this->createCashierUser();

        $before = $this->actingAs($cashier)->get(route('kasir.dashboard'));
        $before->assertStatus(200);
        $before->assertSee('Presensi Shift Anda Belum Tercatat Hari Ini');
        $before->assertSee('Catat Presensi Sekarang');

        $this->actingAs($cashier)->post(route('kasir.attendance.store'), [
            'student_note' => 'Shift Siang Lancar',
        ]);

        $after = $this->actingAs($cashier)->get(route('kasir.dashboard'));
        $after->assertStatus(200);
        $after->assertSee('Presensi Shift Hari Ini Tercatat');
        $after->assertSee('Shift Siang Lancar');
    }
}
