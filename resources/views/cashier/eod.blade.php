@extends('layouts.cashier')

@section('page_title', 'Rekap Harian (End of Day)')

@section('cashier_content')
<div class="max-w-3xl mx-auto">
    @if(session('success'))
    <div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-emerald-500/10 border border-green-200 dark:border-emerald-500/25 text-green-700 dark:text-emerald-300 text-sm flex items-center justify-between shadow-sm">
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-3 text-green-500 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/25 text-red-700 dark:text-red-300 text-sm shadow-sm">
        <div class="flex items-center mb-1 font-medium">
            <svg class="w-5 h-5 mr-2 text-red-500 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>Gagal mengirim laporan EOD:</span>
        </div>
        <ul class="list-disc list-inside space-y-1 text-xs text-red-600 dark:text-red-400 ml-7">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($recap && $recap->is_closed)
    <div class="mb-6 p-4 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/25 text-blue-800 dark:text-blue-200 text-sm flex items-start gap-3 shadow-sm">
        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
            <span class="font-semibold block">Laporan EOD Hari Ini Sudah Terkirim</span>
            <span class="text-xs text-blue-600 dark:text-blue-300">
                Dikirim oleh {{ $recap->recordedBy?->full_name ?? 'Kasir' }} • Status: 
                <strong>{{ $recap->verified_by ? 'Terverifikasi Guru (Disetujui)' : 'Menunggu Verifikasi Guru' }}</strong>.
                Anda masih dapat memperbarui data jika terdapat revisi perhitungan fisik.
            </span>
        </div>
    </div>
    @endif

    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-200 dark:border-zinc-800 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-800/50 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-gray-800 dark:text-zinc-100 text-lg">Buat Laporan Rekap Harian</h3>
                <p class="text-sm text-gray-500 dark:text-zinc-400">Form ini diisi oleh kasir pada akhir shift untuk rekonsiliasi uang fisik dan saldo BNI.</p>
            </div>
            @if($hasOpeningBalance)
                <span class="text-xs text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-500/15 border border-emerald-200 dark:border-emerald-500/30 px-3 py-1 rounded-full font-medium">
                    Loket Dibuka: Rp {{ number_format($recap->opening_bni_balance, 0, ',', '.') }}
                </span>
            @endif
        </div>
        <div class="p-6">
            <form action="{{ route('kasir.eod.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-zinc-300">Tanggal Rekap</label>
                    <input type="date" value="{{ date('Y-m-d') }}" readonly class="bg-gray-100 dark:bg-zinc-800/70 border border-gray-300 dark:border-zinc-700 text-gray-900 dark:text-zinc-300 text-sm rounded-lg block w-full p-2.5 cursor-not-allowed">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-zinc-300">Sisa Saldo BNI Sistem (Otomatis)</label>
                        <input type="text" readonly value="Rp {{ number_format($bniBalanceSystem, 0, ',', '.') }}" class="bg-gray-100 dark:bg-zinc-800/70 border border-gray-300 dark:border-zinc-700 text-gray-900 dark:text-zinc-300 font-semibold text-sm rounded-lg block w-full p-2.5 cursor-not-allowed">
                        <span class="text-xs text-gray-400 dark:text-zinc-500 mt-1 block">Hasil mutasi transaksi harian loket kasir</span>
                    </div>
                    <div>
                        <label for="actual_bni_balance" class="block mb-2 text-sm font-medium text-gray-700 dark:text-zinc-300">Sisa Saldo BNI Aktual (Sesuai mutasi rekening) <span class="text-red-500">*</span></label>
                        <input type="number" step="any" min="0" name="actual_bni_balance" id="actual_bni_balance" required value="{{ old('actual_bni_balance', $recap?->bni_balance_remaining ?? $bniBalanceSystem) }}" class="bg-gray-50 dark:bg-zinc-950/60 border border-gray-300 dark:border-zinc-700 text-gray-900 dark:text-zinc-100 dark:placeholder-zinc-500 text-sm rounded-lg focus:ring-orange-500 focus:border-orange-500 block w-full p-2.5 font-mono font-medium" placeholder="Masukkan saldo akhir di m-banking">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="actual_cash_amount" class="block mb-2 text-sm font-medium text-gray-700 dark:text-zinc-300">Total Uang Tunai Fisik (Rp) <span class="text-red-500">*</span></label>
                        <input type="number" step="any" min="0" name="actual_cash_amount" id="actual_cash_amount" required value="{{ old('actual_cash_amount', $recap?->cash_amount ?? $cashInSystem) }}" class="bg-gray-50 dark:bg-zinc-950/60 border border-gray-300 dark:border-zinc-700 text-gray-900 dark:text-zinc-100 dark:placeholder-zinc-500 text-sm rounded-lg focus:ring-orange-500 focus:border-orange-500 block w-full p-2.5 font-mono font-medium" placeholder="Hitung total uang fisik di laci">
                        <span class="text-xs text-gray-400 dark:text-zinc-500 mt-1 block">Estimasi kas sistem: Rp {{ number_format($cashInSystem, 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <label for="actual_stamp_count" class="block mb-2 text-sm font-medium text-gray-700 dark:text-zinc-300">Sisa Materai Fisik (pcs) <span class="text-red-500">*</span></label>
                        <input type="number" min="0" name="actual_stamp_count" id="actual_stamp_count" required value="{{ old('actual_stamp_count', $recap?->stamp_remaining_value ?? $stampsStock) }}" class="bg-gray-50 dark:bg-zinc-950/60 border border-gray-300 dark:border-zinc-700 text-gray-900 dark:text-zinc-100 dark:placeholder-zinc-500 text-sm rounded-lg focus:ring-orange-500 focus:border-orange-500 block w-full p-2.5 font-mono font-medium" placeholder="Hitung jumlah lembar materai">
                        <span class="text-xs text-gray-400 dark:text-zinc-500 mt-1 block">Sisa stok sistem: {{ $stampsStock }} pcs (valuasi modal @ Rp {{ number_format($stampCostPrice ?? 10000, 0, ',', '.') }}/pcs)</span>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="notes" class="block mb-2 text-sm font-medium text-gray-700 dark:text-zinc-300">Catatan Khusus (Jika ada selisih atau kendala)</label>
                    <textarea name="notes" id="notes" class="bg-gray-50 dark:bg-zinc-950/60 border border-gray-300 dark:border-zinc-700 text-gray-900 dark:text-zinc-100 dark:placeholder-zinc-500 text-sm rounded-lg focus:ring-orange-500 focus:border-orange-500 block w-full p-2.5" rows="3" placeholder="Tuliskan catatan selisih kas fisik vs sistem atau kendala transaksi...">{{ old('notes', $recap?->notes) }}</textarea>
                </div>

                <div class="p-4 bg-yellow-50 dark:bg-amber-500/10 rounded-lg border border-yellow-200 dark:border-amber-500/25 mb-6 flex items-start">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-amber-400 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <p class="text-sm text-yellow-800 dark:text-amber-200">
                        Pastikan semua data yang diinput sudah sesuai dengan fisik sebenarnya. Data rekap harian yang di-submit akan diverifikasi oleh Guru Pembimbing.
                    </p>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('kasir.dashboard') }}" class="text-gray-600 dark:text-zinc-200 bg-white dark:bg-zinc-800 hover:bg-gray-100 dark:hover:bg-zinc-700 border border-gray-300 dark:border-zinc-700 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-colors">
                        Kembali ke Dashboard
                    </a>
                    <button type="submit" class="text-white bg-orange-600 hover:bg-orange-700 focus:ring-4 focus:outline-none focus:ring-orange-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center shadow-sm cursor-pointer">
                        {{ $recap && $recap->is_closed ? 'Perbarui Laporan EOD' : 'Kirim Laporan EOD' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
