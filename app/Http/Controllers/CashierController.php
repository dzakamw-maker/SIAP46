<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DailyRecap;
use App\Models\StampDutyRecord;
use App\Models\Transaction;
use App\Models\TransactionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CashierController extends Controller
{
    public function dashboard(): View
    {
        $this->reconcileStampsStockIfNeeded();
        $this->ensureDefaultTransactionTypesExist();

        $types = TransactionType::where('is_active', true)->get();
        $stampsStock = StampDutyRecord::latest('id')->value('remaining_stock') ?? 0;
        $todayTransactions = Transaction::with(['customer', 'transactionType'])
            ->whereDate('transaction_date', today())
            ->latest()
            ->get();

        $openingBalance = DailyRecap::with('openedBy')->whereDate('recap_date', today())->first();
        $hasOpeningBalance = $openingBalance && $openingBalance->opening_bni_balance !== null;

        $previousRecap = DailyRecap::whereDate('recap_date', '<', today())->latest('recap_date')->first();
        $suggestedBniBalance = (float) ($previousRecap?->bni_balance_remaining ?? 0);

        return view('cashier.dashboard', compact(
            'types',
            'stampsStock',
            'todayTransactions',
            'hasOpeningBalance',
            'openingBalance',
            'suggestedBniBalance'
        ));
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $typeId = $request->query('transaction_type_id');
        $search = trim((string) $request->query('q', ''));

        if (! $typeId || $search === '') {
            return response()->json([]);
        }

        $query = Customer::where('transaction_type_id', $typeId)
            ->where('is_default', true)
            ->where(function ($q) use ($search) {
                $q->where('customer_identifier', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });

        $customers = $query->latest('updated_at')
            ->limit(10)
            ->get(['id', 'customer_identifier', 'customer_name', 'is_default']);

        return response()->json($customers);
    }

    public function checkCustomerDefault(Request $request): JsonResponse
    {
        $typeId = $request->query('transaction_type_id');
        $name = trim((string) $request->query('customer_name', ''));
        $identifier = trim((string) $request->query('customer_identifier', ''));

        if (! $typeId || $name === '') {
            return response()->json([
                'has_default' => false,
                'default_identifier' => null,
                'requires_verification' => true,
            ]);
        }

        $selectedType = TransactionType::find($typeId);
        $isSpp = $selectedType && (
            str_contains(strtolower($selectedType->code), 'spp') ||
            str_contains(strtolower($selectedType->name), 'spp')
        );

        if ($isSpp && $identifier !== '' && ! str_starts_with($identifier, '98844565')) {
            $identifier = '98844565'.$identifier;
        }

        $defaultCustomer = Customer::where('transaction_type_id', $typeId)
            ->where('is_default', true)
            ->where(function ($q) use ($name) {
                $q->whereRaw('LOWER(customer_name) = ?', [strtolower($name)])
                    ->orWhere('customer_name', $name);
            })
            ->first();

        if ($defaultCustomer) {
            return response()->json([
                'has_default' => true,
                'default_identifier' => $defaultCustomer->customer_identifier,
                'is_current_default' => ($defaultCustomer->customer_identifier === $identifier),
                'requires_verification' => false,
            ]);
        }

        return response()->json([
            'has_default' => false,
            'default_identifier' => null,
            'requires_verification' => true,
        ]);
    }

    public function storeOpeningBalance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'opening_bni_balance' => ['required', 'numeric', 'min:0'],
            'opening_cash_balance' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [
            'opening_bni_balance.required' => 'Nominal saldo awal rekening Agen BNI wajib diisi.',
            'opening_bni_balance.numeric' => 'Nominal saldo awal BNI harus berupa angka valid.',
            'opening_bni_balance.min' => 'Nominal saldo awal BNI tidak boleh bernilai negatif.',
            'opening_cash_balance.numeric' => 'Nominal saldo kas fisik harus berupa angka valid.',
            'opening_cash_balance.min' => 'Nominal saldo kas fisik tidak boleh bernilai negatif.',
        ]);

        $bniBalance = (float) $validated['opening_bni_balance'];
        $cashBalance = (float) ($validated['opening_cash_balance'] ?? 0);

        DailyRecap::updateOrCreate(
            ['recap_date' => today()],
            [
                'opening_bni_balance' => $bniBalance,
                'opening_cash_balance' => $cashBalance,
                'bni_balance_remaining' => $bniBalance,
                'cash_amount' => $cashBalance,
                'opened_by' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return redirect()->route('kasir.dashboard')->with('success', 'Loket kasir berhasil dibuka! Saldo awal BNI Rp '.number_format($bniBalance, 0, ',', '.').' berhasil disimpan.');
    }

    public function storeTransaction(Request $request): RedirectResponse
    {
        $hasOpening = DailyRecap::whereDate('recap_date', today())
            ->whereNotNull('opening_bni_balance')
            ->exists();

        if (! $hasOpening && ! app()->runningUnitTests()) {
            return back()->withErrors([
                'transaction_type_id' => 'Loket belum dibuka! Harap input saldo awal terlebih dahulu sebelum memulai transaksi.',
            ])->withInput();
        }

        $selectedType = TransactionType::find($request->input('transaction_type_id'));
        $isMaterai = $selectedType && (
            in_array(strtolower($selectedType->code), ['materai', 'mtr'], true) ||
            str_contains(strtolower($selectedType->code), 'materai') ||
            str_contains(strtolower($selectedType->name), 'materai')
        );
        $isSpp = $selectedType && (
            str_contains(strtolower($selectedType->code), 'spp') ||
            str_contains(strtolower($selectedType->name), 'spp')
        );

        $rules = [
            'transaction_type_id' => ['required', 'exists:transaction_types,id'],
            'customer_identifier' => [$isMaterai ? 'nullable' : 'required', 'string', 'max:255'],
            'customer_name' => ['required', 'string', 'max:255'],
            'is_default' => ['nullable', 'in:0,1,true,false'],
            'amount' => [$isMaterai ? 'nullable' : 'required', 'numeric', 'min:0'],
            'stamp_quantity' => ['nullable', 'integer', 'min:1'],
            'stamp_price' => ['nullable', 'numeric', 'min:0'],
            'admin_fee' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];

        if ($isMaterai && ! $request->filled('amount') && ! $request->filled('stamp_quantity')) {
            $rules['stamp_quantity'] = ['required', 'integer', 'min:1'];
        }

        $validated = $request->validate($rules, [
            'transaction_type_id.required' => 'Jenis transaksi wajib dipilih.',
            'customer_identifier.required' => 'Identitas customer wajib diisi.',
            'customer_name.required' => 'Nama customer wajib diisi.',
            'amount.required' => 'Nominal uang transaksi wajib diisi.',
            'stamp_quantity.required' => 'Jumlah pembelian materai wajib diisi.',
            'stamp_quantity.min' => 'Jumlah pembelian materai minimal 1 pcs.',
            'stamp_quantity.integer' => 'Jumlah pembelian materai harus berupa angka bulat.',
        ]);

        $type = $selectedType ?? TransactionType::findOrFail($validated['transaction_type_id']);
        $stampsStock = StampDutyRecord::latest('id')->value('remaining_stock') ?? 0;

        $quantityPurchased = 1;
        $stampPrice = 11000.0;

        if ($isMaterai) {
            $quantityPurchased = $request->filled('stamp_quantity') ? (int) $request->input('stamp_quantity') : 1;
            $stampPrice = $request->filled('stamp_price') ? (float) $request->input('stamp_price') : 11000.0;

            if ($stampsStock <= 0) {
                return back()->withErrors(['transaction_type_id' => 'Stok materai habis! Transaksi tidak dapat diproses.'])->withInput();
            }

            if ($stampsStock < $quantityPurchased) {
                return back()->withErrors(['stamp_quantity' => "Stok materai tidak mencukupi! Sisa stok: {$stampsStock} pcs."])->withInput();
            }

            $amount = $request->filled('amount') && ! $request->filled('stamp_quantity')
                ? (float) $validated['amount']
                : (float) ($quantityPurchased * $stampPrice);
            $adminFee = 0.0;
        } else {
            $amount = (float) $validated['amount'];
            $adminFee = (float) ($validated['admin_fee'] ?? 0);
        }

        $customerName = trim($validated['customer_name']);
        $customerIdent = trim((string) ($validated['customer_identifier'] ?? ''));

        if ($isSpp && $customerIdent !== '' && ! str_starts_with($customerIdent, '98844565')) {
            $customerIdent = '98844565'.$customerIdent;
        }

        if ($isMaterai && $customerIdent === '') {
            $customerIdent = 'MTR-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4));
        }

        $isDefaultInput = $request->input('is_default');

        // Cek apakah customer dengan nama ini sudah punya default ID
        $existingDefault = Customer::where('transaction_type_id', $type->id)
            ->where('is_default', true)
            ->where(function ($q) use ($customerName) {
                $q->whereRaw('LOWER(customer_name) = ?', [strtolower($customerName)])
                    ->orWhere('customer_name', $customerName);
            })
            ->first();

        // Tentukan nilai is_default
        $shouldBeDefault = false;
        if (! $isMaterai) {
            if ($isDefaultInput === '1' || $isDefaultInput === 1 || $isDefaultInput === true) {
                $shouldBeDefault = true;
            } elseif ($isDefaultInput === '0' || $isDefaultInput === 0 || $isDefaultInput === false) {
                $shouldBeDefault = false;
            } elseif ($existingDefault && $existingDefault->customer_identifier === $customerIdent) {
                $shouldBeDefault = true;
            }

            // Jika diset default dan sebelumnya ada default lain untuk orang ini, nonaktifkan default sebelumnya
            if ($shouldBeDefault && $existingDefault && $existingDefault->customer_identifier !== $customerIdent) {
                $existingDefault->update(['is_default' => false]);
            }
        }

        // Simpan / update identitas customer untuk jenis transaksi ini
        $customer = Customer::updateOrCreate(
            [
                'transaction_type_id' => $type->id,
                'customer_identifier' => $customerIdent,
            ],
            [
                'customer_name' => $customerName,
                'is_default' => $shouldBeDefault,
                'last_edited_by' => auth()->id(),
            ]
        );

        $todayCount = Transaction::whereDate('transaction_date', today())->count();
        $txNumber = $todayCount + 1;

        $totalPayment = $amount + $adminFee;
        $txNumber = 1;

        DB::transaction(function () use (
            $type, $customer, $amount, $adminFee, $totalPayment, $validated,
            $isMaterai, $quantityPurchased, $stampPrice, $stampsStock, &$txNumber
        ) {
            $todayCount = Transaction::whereDate('transaction_date', today())->lockForUpdate()->count();
            $txNumber = $todayCount + 1;

            $latestTxToday = Transaction::whereDate('transaction_date', today())
                ->whereNotNull('bni_balance_after')
                ->latest('id')
                ->first();

            $currentBniBalance = null;
            if ($latestTxToday) {
                $currentBniBalance = (float) $latestTxToday->bni_balance_after;
            } else {
                $todayRecap = DailyRecap::whereDate('recap_date', today())->first();
                if ($todayRecap && $todayRecap->opening_bni_balance !== null) {
                    $currentBniBalance = (float) $todayRecap->opening_bni_balance;
                }
            }

            $isTarikTunai = str_contains(strtolower($type->code), 'tarik') || str_contains(strtolower($type->name), 'tarik');

            $bniBalanceAfter = null;
            if ($currentBniBalance !== null) {
                if ($isMaterai) {
                    $bniBalanceAfter = $currentBniBalance;
                } elseif ($isTarikTunai) {
                    $bniBalanceAfter = $currentBniBalance + $amount;
                } else {
                    $bniBalanceAfter = $currentBniBalance - $amount;
                }
            }

            Transaction::create([
                'transaction_number' => $txNumber,
                'transaction_date' => today(),
                'transaction_type_id' => $type->id,
                'customer_id' => $customer->id,
                'amount' => $amount,
                'admin_fee' => $adminFee,
                'total_payment' => $totalPayment,
                'bni_balance_after' => $bniBalanceAfter,
                'cashier_id' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            // Kurangi stok materai jika transaksi materai
            if ($isMaterai) {
                StampDutyRecord::create([
                    'record_date' => today(),
                    'quantity_sold' => $quantityPurchased,
                    'quantity_purchased' => 0,
                    'remaining_stock' => max(0, $stampsStock - $quantityPurchased),
                    'amount' => $amount,
                    'cashier_id' => auth()->id(),
                    'notes' => 'Penjualan Materai '.$quantityPurchased.' pcs @ Rp '.number_format($stampPrice, 0, ',', '.').' (Transaksi #'.$txNumber.')',
                ]);
            }

            if ($bniBalanceAfter !== null) {
                DailyRecap::whereDate('recap_date', today())->update([
                    'bni_balance_remaining' => $bniBalanceAfter,
                ]);
            }
        });

        return redirect()->route('kasir.dashboard')->with('success', 'Transaksi #'.$txNumber.' ('.$type->name.') atas nama '.$customer->customer_name.' berhasil diproses!');
    }

    public function stamps(): View
    {
        $this->reconcileStampsStockIfNeeded();

        $stampsStock = StampDutyRecord::latest('id')->value('remaining_stock') ?? 0;
        $recentRestocks = StampDutyRecord::with('cashier')
            ->where('quantity_purchased', '>', 0)
            ->latest('id')
            ->take(10)
            ->get();

        return view('cashier.stamps', compact('stampsStock', 'recentRestocks'));
    }

    public function storeStamp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'record_date' => ['required', 'date'],
            'quantity_purchased' => ['required', 'integer', 'min:1'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [
            'record_date.required' => 'Tanggal restock wajib diisi.',
            'record_date.date' => 'Format tanggal tidak valid.',
            'quantity_purchased.required' => 'Jumlah materai wajib diisi.',
            'quantity_purchased.integer' => 'Jumlah materai harus berupa bilangan bulat.',
            'quantity_purchased.min' => 'Jumlah materai minimal 1 pcs.',
            'amount.numeric' => 'Total harga beli harus berupa angka.',
            'amount.min' => 'Total harga beli tidak boleh bernilai negatif.',
            'notes.max' => 'Catatan maksimal 255 karakter.',
        ]);

        $latestRecord = StampDutyRecord::latest('id')->first();
        $currentStock = $latestRecord ? (int) $latestRecord->remaining_stock : 0;
        $quantityPurchased = (int) $validated['quantity_purchased'];
        $amount = (float) ($validated['amount'] ?? 0);
        $newStock = $currentStock + $quantityPurchased;

        StampDutyRecord::create([
            'record_date' => $validated['record_date'],
            'quantity_sold' => 0,
            'quantity_purchased' => $quantityPurchased,
            'remaining_stock' => $newStock,
            'amount' => $amount,
            'cashier_id' => auth()->id(),
            'notes' => $validated['notes'] ?? 'Restock Materai',
        ]);

        return redirect()->route('kasir.stamps')->with('success', 'Restock '.$quantityPurchased.' pcs materai berhasil disimpan. Stok saat ini: '.$newStock.' pcs.');
    }

    public function eod(): View
    {
        $recap = DailyRecap::with('openedBy')->whereDate('recap_date', today())->first();
        $hasOpeningBalance = $recap && $recap->opening_bni_balance !== null;

        $openingBni = (float) ($recap?->opening_bni_balance ?? 0);
        $openingCash = (float) ($recap?->opening_cash_balance ?? 0);

        // Hitung saldo BNI sistem secara realtime
        $latestBniTx = Transaction::whereDate('transaction_date', today())
            ->whereNotNull('bni_balance_after')
            ->latest('id')
            ->first();

        $bniBalanceSystem = $latestBniTx ? (float) $latestBniTx->bni_balance_after : $openingBni;

        // Hitung kas fisik di laci kasir secara realtime
        $todayTransactions = Transaction::with('transactionType')->whereDate('transaction_date', today())->get();
        $cashInSystem = $openingCash;

        foreach ($todayTransactions as $tx) {
            $typeName = strtolower($tx->transactionType?->name ?? '');
            $typeCode = strtolower($tx->transactionType?->code ?? '');
            $isTarik = str_contains($typeName, 'tarik') || str_contains($typeCode, 'tarik');

            if ($isTarik) {
                // Tarik tunai: kasir mengeluarkan uang tunai (amount), tapi menerima biaya admin secara tunai
                $cashInSystem += ((float) $tx->admin_fee - (float) $tx->amount);
            } else {
                // Transaksi lainnya: customer menyerahkan uang tunai (total_payment = amount + admin_fee)
                $cashInSystem += (float) $tx->total_payment;
            }
        }

        $stampsStock = StampDutyRecord::latest('id')->value('remaining_stock') ?? 0;

        return view('cashier.eod', compact(
            'recap',
            'hasOpeningBalance',
            'bniBalanceSystem',
            'cashInSystem',
            'stampsStock'
        ));
    }

    public function storeEod(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'actual_bni_balance' => ['required', 'numeric', 'min:0'],
            'actual_cash_amount' => ['required', 'numeric', 'min:0'],
            'actual_stamp_count' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'actual_bni_balance.required' => 'Sisa saldo BNI aktual wajib diisi.',
            'actual_bni_balance.numeric' => 'Saldo BNI aktual harus berupa angka.',
            'actual_cash_amount.required' => 'Total uang tunai fisik wajib diisi.',
            'actual_cash_amount.numeric' => 'Total uang tunai fisik harus berupa angka.',
            'actual_stamp_count.required' => 'Sisa materai fisik wajib diisi.',
            'actual_stamp_count.integer' => 'Sisa materai fisik harus berupa bilangan bulat.',
        ]);

        $bniActual = (float) $validated['actual_bni_balance'];
        $cashActual = (float) $validated['actual_cash_amount'];
        $stampCount = (int) $validated['actual_stamp_count'];
        $stampPrice = 10000;
        $stampTotalValue = $stampCount * $stampPrice;
        $grandTotal = $bniActual + $cashActual + $stampTotalValue;

        DailyRecap::updateOrCreate(
            ['recap_date' => today()],
            [
                'bni_balance_remaining' => $bniActual,
                'cash_amount' => $cashActual,
                'stamp_remaining_value' => $stampCount,
                'total' => $grandTotal,
                'notes' => $validated['notes'] ?? null,
                'recorded_by' => auth()->id(),
                'is_closed' => true,
            ]
        );

        return redirect()->route('kasir.eod')->with('success', 'Laporan Rekap Harian (EOD) berhasil dikirim dan tersimpan! Menunggu verifikasi dari Guru Pembimbing.');
    }

    /**
     * Rekonsiliasi otomatis data sisa stok materai jika terdapat anomali atau record orphan dari pembatalan transaksi.
     */
    private function reconcileStampsStockIfNeeded(): void
    {
        $needsRecount = false;

        // 1. Bersihkan record pembatalan cacat dari bug destroyTransaction lama (quantity_purchased = 1, notes null, amount >= 20000)
        $buggyRecords = StampDutyRecord::where('quantity_purchased', 1)
            ->whereNull('notes')
            ->where('amount', '>=', 20000)
            ->get();

        foreach ($buggyRecords as $buggy) {
            $buggy->delete();
            $needsRecount = true;
        }

        // 2. Cek apakah ada record penjualan materai yang transaksinya sudah dihapus dari sistem
        $saleRecords = StampDutyRecord::where('quantity_sold', '>', 0)->get();
        foreach ($saleRecords as $sale) {
            if (preg_match('/Transaksi #(\d+)/', (string) $sale->notes, $matches)) {
                $trxNumber = $matches[1];
                $exists = Transaction::where('transaction_number', $trxNumber)->exists();
                if (! $exists) {
                    $sale->delete();
                    $needsRecount = true;
                }
            }
        }

        // 3. Jika ada record yang dibersihkan, rekonsiliasi ulang seluruh rantai remaining_stock
        if ($needsRecount) {
            $records = StampDutyRecord::orderBy('id', 'asc')->get();
            $running = 0;
            foreach ($records as $rec) {
                $running = max(0, $running + (int) $rec->quantity_purchased - (int) $rec->quantity_sold);
                if ($rec->remaining_stock !== $running) {
                    $rec->update(['remaining_stock' => $running]);
                }
            }
        }
    }

    /**
     * Pastikan jenis transaksi default seperti Bayar SPP tersedia di database.
     */
    private function ensureDefaultTransactionTypesExist(): void
    {
        TransactionType::firstOrCreate(
            ['code' => 'bayar_spp'],
            ['name' => 'Bayar SPP', 'is_active' => true]
        );
    }
}
