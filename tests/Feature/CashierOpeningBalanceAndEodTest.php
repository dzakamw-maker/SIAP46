<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DailyRecap;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\TransactionType;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierOpeningBalanceAndEodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    private function createCashierUser(string $username = 'kasir1', string $fullName = 'Agus Kasir'): User
    {
        $kasirRole = Role::firstOrCreate(['name' => 'Kasir']);

        return User::create([
            'role_id' => $kasirRole->id,
            'full_name' => $fullName,
            'username' => $username,
            'password' => 'secret123',
            'is_active' => true,
        ]);
    }

    public function test_cashier_dashboard_shows_unopened_banner_when_no_opening_balance(): void
    {
        $cashier = $this->createCashierUser();

        $response = $this->actingAs($cashier)->get(route('kasir.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Loket Kasir Belum Dibuka Hari Ini');
        $response->assertSee('Buka Loket Dahulu');
        $response->assertSee('Buka Loket Sekarang');
    }

    public function test_cashier_can_store_opening_balance_successfully(): void
    {
        $cashier = $this->createCashierUser();

        $response = $this->actingAs($cashier)->post(route('kasir.opening-balance.store'), [
            'opening_bni_balance' => 5000000,
            'opening_cash_balance' => 500000,
            'notes' => 'Loket pagi dibuka dengan lancar',
        ]);

        $response->assertRedirect(route('kasir.dashboard'));
        $response->assertSessionHas('success');

        $recap = DailyRecap::whereDate('recap_date', today())->first();
        $this->assertNotNull($recap);
        $this->assertEquals(5000000, (float) $recap->opening_bni_balance);
        $this->assertEquals(500000, (float) $recap->opening_cash_balance);
        $this->assertEquals(5000000, (float) $recap->bni_balance_remaining);
        $this->assertEquals(500000, (float) $recap->cash_amount);
        $this->assertEquals($cashier->id, $recap->opened_by);

        // Dashboard sekarang menampilkan loket aktif
        $dashboardResponse = $this->actingAs($cashier)->get(route('kasir.dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Loket Hari Ini Aktif');
        $dashboardResponse->assertSee('Agus Kasir');
        $dashboardResponse->assertSee('5.000.000');
    }

    public function test_another_cashier_sees_opened_loket_and_can_transact(): void
    {
        $cashier1 = $this->createCashierUser('kasir1', 'Kasir Pertama');
        $cashier2 = $this->createCashierUser('kasir2', 'Kasir Kedua');

        // Kasir 1 membuka loket
        $this->actingAs($cashier1)->post(route('kasir.opening-balance.store'), [
            'opening_bni_balance' => 7500000,
            'opening_cash_balance' => 1000000,
        ]);

        // Kasir 2 masuk ke dashboard: tidak ditanya saldo awal lagi, banner aktif terlihat
        $response = $this->actingAs($cashier2)->get(route('kasir.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Loket Hari Ini Aktif');
        $response->assertSee('Kasir Pertama');
        $response->assertSee('7.500.000');
        $response->assertSee('Proses Transaksi');
        $response->assertDontSee('Buka Loket Dahulu');
    }

    public function test_transaction_calculates_and_updates_running_bni_balance(): void
    {
        $cashier = $this->createCashierUser();
        $type = TransactionType::firstOrCreate(
            ['code' => 'transfer_bni'],
            ['name' => 'Transfer BNI', 'is_active' => true]
        );

        // Buka loket dengan saldo awal 10.000.000
        $this->actingAs($cashier)->post(route('kasir.opening-balance.store'), [
            'opening_bni_balance' => 10000000,
            'opening_cash_balance' => 500000,
        ]);

        // Buat transaksi transfer 2.000.000
        $this->actingAs($cashier)->post(route('kasir.transactions.store'), [
            'transaction_type_id' => $type->id,
            'customer_name' => 'Budi Santoso',
            'customer_identifier' => '0123456789',
            'amount' => 2000000,
            'admin_fee' => 5000,
        ]);

        $tx = Transaction::whereDate('transaction_date', today())->latest('id')->first();
        $this->assertNotNull($tx);
        $this->assertEquals(2000000, (float) $tx->amount);
        $this->assertEquals(5000, (float) $tx->admin_fee);
        $this->assertEquals(2005000, (float) $tx->total_payment);
        $this->assertEquals(8000000, (float) $tx->bni_balance_after); // 10.000.000 - 2.000.000

        $recap = DailyRecap::whereDate('recap_date', today())->first();
        $this->assertNotNull($recap);
        $this->assertEquals(8000000, (float) $recap->bni_balance_remaining);
    }

    public function test_tarik_tunai_increases_bni_balance(): void
    {
        $cashier = $this->createCashierUser();
        $tarikType = TransactionType::firstOrCreate(
            ['code' => 'tarik_tunai'],
            ['name' => 'Tarik Tunai', 'is_active' => true]
        );

        // Buka loket dengan saldo awal 5.000.000
        $this->actingAs($cashier)->post(route('kasir.opening-balance.store'), [
            'opening_bni_balance' => 5000000,
            'opening_cash_balance' => 2000000,
        ]);

        // Customer tarik tunai 1.000.000 (uang masuk ke rekening agen BNI, fisik keluar ke customer)
        $this->actingAs($cashier)->post(route('kasir.transactions.store'), [
            'transaction_type_id' => $tarikType->id,
            'customer_name' => 'Siti Rahma',
            'customer_identifier' => '9876543210',
            'amount' => 1000000,
            'admin_fee' => 5000,
        ]);

        $tx = Transaction::whereDate('transaction_date', today())->latest('id')->first();
        $this->assertNotNull($tx);
        $this->assertEquals(1000000, (float) $tx->amount);
        $this->assertEquals(6000000, (float) $tx->bni_balance_after); // 5.000.000 + 1.000.000

        $recap = DailyRecap::whereDate('recap_date', today())->first();
        $this->assertNotNull($recap);
        $this->assertEquals(6000000, (float) $recap->bni_balance_remaining);
    }

    public function test_cashier_can_view_and_submit_eod_report(): void
    {
        $cashier = $this->createCashierUser();

        // Buka loket
        $this->actingAs($cashier)->post(route('kasir.opening-balance.store'), [
            'opening_bni_balance' => 5000000,
            'opening_cash_balance' => 1000000,
        ]);

        // Cek halaman EOD
        $response = $this->actingAs($cashier)->get(route('kasir.eod'));
        $response->assertStatus(200);
        $response->assertSee('Buat Laporan Rekap Harian');
        $response->assertSee('5.000.000');

        // Submit EOD
        $submitResponse = $this->actingAs($cashier)->post(route('kasir.eod.store'), [
            'actual_bni_balance' => 4950000,
            'actual_cash_amount' => 1050000,
            'actual_stamp_count' => 25,
            'notes' => 'Rekap harian klop, selisih 0',
        ]);

        $submitResponse->assertRedirect(route('kasir.eod'));
        $submitResponse->assertSessionHas('success');

        $recap = DailyRecap::whereDate('recap_date', today())->first();
        $this->assertNotNull($recap);
        $this->assertEquals(4950000, (float) $recap->bni_balance_remaining);
        $this->assertEquals(1050000, (float) $recap->cash_amount);
        $this->assertEquals(25, (int) $recap->stamp_remaining_value);
        $this->assertTrue($recap->is_closed);
        $this->assertEquals($cashier->id, $recap->recorded_by);
        $this->assertEquals('Rekap harian klop, selisih 0', $recap->notes);
    }
}
