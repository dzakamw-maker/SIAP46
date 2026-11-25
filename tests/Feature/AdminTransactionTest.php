<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DailyRecap;
use App\Models\Role;
use App\Models\StampDutyRecord;
use App\Models\Transaction;
use App\Models\TransactionType;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTransactionTest extends TestCase
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
            'full_name' => 'Admin Guru',
            'username' => 'admin_guru',
            'password' => 'secret123',
            'is_active' => true,
        ]);
    }

    private function createCashierUser(): User
    {
        $kasirRole = Role::firstOrCreate(['name' => 'Kasir']);

        return User::create([
            'role_id' => $kasirRole->id,
            'full_name' => 'Kasir Siswa',
            'username' => 'kasir_siswa',
            'password' => 'secret123',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_transactions_list(): void
    {
        $admin = $this->createAdminUser();
        $cashier = $this->createCashierUser();
        $type = TransactionType::firstOrCreate(
            ['code' => 'setor_tunai_bni'],
            ['name' => 'Setor Tunai BNI', 'is_active' => true]
        );

        $customer = Customer::create([
            'transaction_type_id' => $type->id,
            'customer_identifier' => '12511292',
            'customer_name' => 'Dzaka Testing',
        ]);

        Transaction::create([
            'transaction_number' => 1,
            'transaction_date' => today(),
            'transaction_type_id' => $type->id,
            'customer_id' => $customer->id,
            'amount' => 50000,
            'admin_fee' => 1000,
            'total_payment' => 51000,
            'cashier_id' => $cashier->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.transactions'));

        $response->assertStatus(200);
        $response->assertSee('#1');
        $response->assertSee('Dzaka Testing');
        $response->assertSee('Setor Tunai BNI');
        $response->assertSee('Hapus');
    }

    public function test_admin_can_delete_transaction_and_cleans_up_unused_customer(): void
    {
        $admin = $this->createAdminUser();
        $cashier = $this->createCashierUser();
        $type = TransactionType::firstOrCreate(
            ['code' => 'topup_dana'],
            ['name' => 'TOPUP-DANA', 'is_active' => true]
        );

        $customer = Customer::create([
            'transaction_type_id' => $type->id,
            'customer_identifier' => '08123456789',
            'customer_name' => 'Budi Dana',
        ]);

        $trx = Transaction::create([
            'transaction_number' => 1,
            'transaction_date' => today(),
            'transaction_type_id' => $type->id,
            'customer_id' => $customer->id,
            'amount' => 20000,
            'admin_fee' => 1000,
            'total_payment' => 21000,
            'cashier_id' => $cashier->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.transactions.destroy', $trx->id));

        $response->assertRedirect(route('admin.transactions'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('transactions', ['id' => $trx->id]);
        // Customer should also be cleaned up since 0 transactions remain
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_admin_deleting_transaction_retains_customer_if_other_transactions_exist(): void
    {
        $admin = $this->createAdminUser();
        $cashier = $this->createCashierUser();
        $type = TransactionType::firstOrCreate(
            ['code' => 'topup_dana'],
            ['name' => 'TOPUP-DANA', 'is_active' => true]
        );

        $customer = Customer::create([
            'transaction_type_id' => $type->id,
            'customer_identifier' => '08123456789',
            'customer_name' => 'Budi Dana',
        ]);

        $trx1 = Transaction::create([
            'transaction_number' => 1,
            'transaction_date' => today(),
            'transaction_type_id' => $type->id,
            'customer_id' => $customer->id,
            'amount' => 20000,
            'admin_fee' => 1000,
            'total_payment' => 21000,
            'cashier_id' => $cashier->id,
        ]);

        $trx2 = Transaction::create([
            'transaction_number' => 2,
            'transaction_date' => today(),
            'transaction_type_id' => $type->id,
            'customer_id' => $customer->id,
            'amount' => 50000,
            'admin_fee' => 1000,
            'total_payment' => 51000,
            'cashier_id' => $cashier->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.transactions.destroy', $trx1->id));

        $response->assertRedirect(route('admin.transactions'));
        $this->assertDatabaseMissing('transactions', ['id' => $trx1->id]);
        $this->assertDatabaseHas('transactions', ['id' => $trx2->id]);
        // Customer should still exist
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    public function test_admin_deleting_materai_transaction_refunds_stamp_stock(): void
    {
        $admin = $this->createAdminUser();
        $cashier = $this->createCashierUser();
        $type = TransactionType::firstOrCreate(
            ['code' => 'materai'],
            ['name' => 'Materai', 'is_active' => true]
        );

        StampDutyRecord::create([
            'record_date' => today(),
            'quantity_sold' => 1,
            'quantity_purchased' => 0,
            'remaining_stock' => 9,
            'amount' => 10000,
        ]);

        $customer = Customer::create([
            'transaction_type_id' => $type->id,
            'customer_identifier' => 'SISWA-1',
            'customer_name' => 'Siswa Pembeli',
        ]);

        $trx = Transaction::create([
            'transaction_number' => 1,
            'transaction_date' => today(),
            'transaction_type_id' => $type->id,
            'customer_id' => $customer->id,
            'amount' => 10000,
            'admin_fee' => 0,
            'total_payment' => 10000,
            'cashier_id' => $cashier->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.transactions.destroy', $trx->id));

        $response->assertRedirect(route('admin.transactions'));
        $this->assertDatabaseMissing('transactions', ['id' => $trx->id]);

        $latestStamp = StampDutyRecord::latest('id')->first();
        $this->assertEquals(10, $latestStamp->remaining_stock);
    }

    public function test_cashier_cannot_delete_transaction(): void
    {
        $cashier = $this->createCashierUser();
        $type = TransactionType::firstOrCreate(
            ['code' => 'topup_dana'],
            ['name' => 'TOPUP-DANA', 'is_active' => true]
        );

        $customer = Customer::create([
            'transaction_type_id' => $type->id,
            'customer_identifier' => '08123456789',
            'customer_name' => 'Budi Dana',
        ]);

        $trx = Transaction::create([
            'transaction_number' => 1,
            'transaction_date' => today(),
            'transaction_type_id' => $type->id,
            'customer_id' => $customer->id,
            'amount' => 20000,
            'admin_fee' => 1000,
            'total_payment' => 21000,
            'cashier_id' => $cashier->id,
        ]);

        $response = $this->actingAs($cashier)->delete(route('admin.transactions.destroy', $trx->id));

        // Cashier role should be denied (403) from admin transaction deletion
        $response->assertStatus(403);
        $this->assertDatabaseHas('transactions', ['id' => $trx->id]);
    }

    public function test_admin_dashboard_displays_recent_transactions_from_database(): void
    {
        $admin = $this->createAdminUser();
        $cashier = $this->createCashierUser();
        $type = TransactionType::firstOrCreate(
            ['code' => 'setor_tunai_bni'],
            ['name' => 'Setor Tunai BNI', 'is_active' => true]
        );

        $customer = Customer::create([
            'transaction_type_id' => $type->id,
            'customer_identifier' => '99887766',
            'customer_name' => 'Ahmad Sujatmiko',
        ]);

        Transaction::create([
            'transaction_number' => 1,
            'transaction_date' => today(),
            'transaction_type_id' => $type->id,
            'customer_id' => $customer->id,
            'amount' => 750000,
            'admin_fee' => 2000,
            'total_payment' => 752000,
            'cashier_id' => $cashier->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Transaksi Terbaru');
        $response->assertSee('Ahmad Sujatmiko');
        $response->assertSee('Setor Tunai BNI');
        $response->assertSee('Rp 750.000');
        $response->assertSee('Selesai');
    }

    public function test_admin_deleting_transaction_recalculates_subsequent_bni_balances_and_daily_recap(): void
    {
        $admin = $this->createAdminUser();
        $cashier = $this->createCashierUser();
        $setorType = TransactionType::firstOrCreate(
            ['code' => 'setor_tunai_bni'],
            ['name' => 'Setor Tunai BNI', 'is_active' => true]
        );
        $tarikType = TransactionType::firstOrCreate(
            ['code' => 'tarik_tunai'],
            ['name' => 'Tarik Tunai', 'is_active' => true]
        );

        // Buka loket saldo awal 10.000.000
        DailyRecap::create([
            'recap_date' => today(),
            'opening_bni_balance' => 10000000,
            'opening_cash_balance' => 500000,
            'bni_balance_remaining' => 10000000,
            'cash_amount' => 500000,
            'opened_by' => $cashier->id,
        ]);

        // Transaksi #1: Setor 2.000.000 -> sisa 8.000.000
        $this->actingAs($cashier)->post(route('kasir.transactions.store'), [
            'transaction_type_id' => $setorType->id,
            'customer_identifier' => '1111111111',
            'customer_name' => 'Nasabah Satu',
            'amount' => 2000000,
            'admin_fee' => 2500,
        ]);

        // Transaksi #2: Tarik 500.000 -> sisa 8.500.000
        $this->actingAs($cashier)->post(route('kasir.transactions.store'), [
            'transaction_type_id' => $tarikType->id,
            'customer_identifier' => '2222222222',
            'customer_name' => 'Nasabah Dua',
            'amount' => 500000,
            'admin_fee' => 3000,
        ]);

        // Transaksi #3: Setor 1.000.000 -> sisa 7.500.000
        $this->actingAs($cashier)->post(route('kasir.transactions.store'), [
            'transaction_type_id' => $setorType->id,
            'customer_identifier' => '3333333333',
            'customer_name' => 'Nasabah Tiga',
            'amount' => 1000000,
            'admin_fee' => 2500,
        ]);

        $tx1 = Transaction::where('transaction_number', 1)->first();
        $tx2 = Transaction::where('transaction_number', 2)->first();
        $tx3 = Transaction::where('transaction_number', 3)->first();

        $this->assertEquals(8000000, (float) $tx1->bni_balance_after);
        $this->assertEquals(8500000, (float) $tx2->bni_balance_after);
        $this->assertEquals(7500000, (float) $tx3->bni_balance_after);

        // Hapus Transaksi #1 -> saldo akhir harus dihitung ulang: 10.000.000 + 500.000 - 1.000.000 = 9.500.000
        $this->actingAs($admin)->delete(route('admin.transactions.destroy', $tx1->id))
            ->assertRedirect(route('admin.transactions'));

        $tx2->refresh();
        $tx3->refresh();
        $this->assertEquals(10500000, (float) $tx2->bni_balance_after);
        $this->assertEquals(9500000, (float) $tx3->bni_balance_after);

        $recap = DailyRecap::whereDate('recap_date', today())->first();
        $this->assertEquals(9500000, (float) $recap->bni_balance_remaining);
    }

    public function test_admin_deleting_materai_transaction_1_does_not_delete_stamp_record_10(): void
    {
        $admin = $this->createAdminUser();
        $cashier = $this->createCashierUser();
        $type = TransactionType::firstOrCreate(
            ['code' => 'materai'],
            ['name' => 'Materai', 'is_active' => true]
        );

        StampDutyRecord::create([
            'record_date' => today(),
            'quantity_sold' => 0,
            'quantity_purchased' => 20,
            'remaining_stock' => 20,
            'amount' => 200000,
        ]);

        $customer = Customer::create([
            'transaction_type_id' => $type->id,
            'customer_identifier' => 'MTR-1',
            'customer_name' => 'Pembeli 1',
        ]);

        // Transaksi #1 (1 pcs) dan Transaksi #10 (5 pcs)
        $trx1 = Transaction::create([
            'transaction_number' => 1,
            'transaction_date' => today(),
            'transaction_type_id' => $type->id,
            'customer_id' => $customer->id,
            'amount' => 11000,
            'admin_fee' => 0,
            'total_payment' => 11000,
            'cashier_id' => $cashier->id,
        ]);

        // Catat record penjualan #10 lebih dulu agar latest('id') akan menyentuhnya jika wildcard salah
        $stamp1 = StampDutyRecord::create([
            'record_date' => today(),
            'quantity_sold' => 1,
            'quantity_purchased' => 0,
            'remaining_stock' => 19,
            'amount' => 11000,
            'notes' => 'Penjualan Materai 1 pcs @ Rp 11.000 (Transaksi #1)',
        ]);

        $stamp10 = StampDutyRecord::create([
            'record_date' => today(),
            'quantity_sold' => 5,
            'quantity_purchased' => 0,
            'remaining_stock' => 14,
            'amount' => 55000,
            'notes' => 'Penjualan Materai 5 pcs @ Rp 11.000 (Transaksi #10)',
        ]);

        $this->actingAs($admin)->delete(route('admin.transactions.destroy', $trx1->id))
            ->assertRedirect(route('admin.transactions'));

        // Record penjualan #1 terhapus, record #10 tetap utuh dan stok tersisa jadi 15 (20 - 5)
        $this->assertDatabaseMissing('stamp_duty_records', ['id' => $stamp1->id]);
        $this->assertDatabaseHas('stamp_duty_records', ['id' => $stamp10->id]);
        $stamp10->refresh();
        $this->assertEquals(15, $stamp10->remaining_stock);
    }
}
