@extends('layouts.cashier')

@section('page_title', 'Rekap Harian (End of Day)')

@section('cashier_content')
<div class="max-w-3xl mx-auto">
    @if(session('success'))
    <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm flex items-center justify-between shadow-sm">
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-3 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm shadow-sm">
        <div class="flex items-center mb-1 font-medium">
            <svg class="w-5 h-5 mr-2 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>Gagal mengirim laporan EOD:</span>
        </div>
        <ul class="list-disc list-inside space-y-1 text-xs text-red-600 ml-7">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($recap && $recap->is_closed)
    <div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-sm flex items-start gap-3 shadow-sm">
        <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
            <span class="font-semibold block">Laporan EOD Hari Ini Sudah Terkirim</span>
            <span class="text-xs text-blue-600">
                Dikirim oleh {{ $recap->recordedBy?->full_name ?? 'Kasir' }} • Status: 
                <strong>{{ $recap->verified_by ? 'Terverifikasi Guru (Disetujui)' : 'Menunggu Verifikasi Guru' }}</strong>.
                Anda masih dapat memperbarui data jika terdapat revisi perhitungan fisik.
            </span>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-gray-800 text-lg">Buat Laporan Rekap Harian</h3>
                <p class="text-sm text-gray-500">Form ini diisi oleh kasir pada akhir shift untuk rekonsiliasi uang fisik dan saldo BNI.</p>
            </div>
            @if($hasOpeningBalance)
                <span class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full font-medium">
                    Loket Dibuka: Rp {{ number_format($recap->opening_bni_balance, 0, ',', '.') }}
                </span>
            @endif
        </div>
        <div class="p-6">
            <form action="{{ route('kasir.eod.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-700">Tanggal Rekap</label>
                    <input type="date" value="{{ date('Y-m-d') }}" readonly class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 cursor-not-allowed">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Sisa Saldo BNI Sistem (Otomatis)</label>
                        <input type="text" readonly value="Rp {{ number_format($bniBalanceSystem, 0, ',', '.') }}" class="bg-gray-100 border border-gray-300 text-gray-900 font-semibold text-sm rounded-lg block w-full p-2.5 cursor-not-allowed">
                        <span class="text-xs text-gray-400 mt-1 block">Hasil mutasi transaksi harian loket kasir</span>
                    </div>
                    <div>
                        <label for="actual_bni_balance" class="block mb-2 text-sm font-medium text-gray-700">Sisa Saldo BNI Aktual (Sesuai mutasi rekening) <span class="text-red-500">*</span></label>
                        <input type="number" step="any" min="0" name="actual_bni_balance" id="actual_bni_balance" required value="{{ old('actual_bni_balance', $recap?->bni_balance_remaining ?? $bniBalanceSystem) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-orange-500 focus:border-orange-500 block w-full p-2.5 font-mono font-medium" placeholder="Masukkan saldo akhir di m-banking">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="actual_cash_amount" class="block mb-2 text-sm font-medium text-gray-700">Total Uang Tunai Fisik (Rp) <span class="text-red-500">*</span></label>
                        <input type="number" step="any" min="0" name="actual_cash_amount" id="actual_cash_amount" required value="{{ old('actual_cash_amount', $recap?->cash_amount ?? $cashInSystem) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-orange-500 focus:border-orange-500 block w-full p-2.5 font-mono font-medium" placeholder="Hitung total uang fisik di laci">
                        <span class="text-xs text-gray-400 mt-1 block">Estimasi kas sistem: Rp {{ number_format($cashInSystem, 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <label for="actual_stamp_count" class="block mb-2 text-sm font-medium text-gray-700">Sisa Materai Fisik (pcs) <span class="text-red-500">*</span></label>
                        <input type="number" min="0" name="actual_stamp_count" id="actual_stamp_count" required value="{{ old('actual_stamp_count', $recap?->stamp_remaining_value ?? $stampsStock) }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-orange-500 focus:border-orange-500 block w-full p-2.5 font-mono font-medium" placeholder="Hitung jumlah lembar materai">
                        <span class="text-xs text-gray-400 mt-1 block">Sisa stok materai sistem: {{ $stampsStock }} pcs</span>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="notes" class="block mb-2 text-sm font-medium text-gray-700">Catatan Khusus (Jika ada selisih atau kendala)</label>
                    <textarea name="notes" id="notes" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-orange-500 focus:border-orange-500 block w-full p-2.5" rows="3" placeholder="Tuliskan catatan selisih kas fisik vs sistem atau kendala transaksi...">{{ old('notes', $recap?->notes) }}</textarea>
                </div>

                <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-200 mb-6 flex items-start">
                    <svg class="w-5 h-5 text-yellow-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <p class="text-sm text-yellow-800">
                        Pastikan semua data yang diinput sudah sesuai dengan fisik sebenarnya. Data rekap harian yang di-submit akan diverifikasi oleh Guru Pembimbing.
                    </p>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('kasir.dashboard') }}" class="text-gray-600 bg-white hover:bg-gray-100 border border-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-colors">
                        Kembali ke Dashboard
                    </a>
                    <button type="submit" class="text-white bg-orange-600 hover:bg-orange-700 focus:ring-4 focus:outline-none focus:ring-orange-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center shadow-sm">
                        {{ $recap && $recap->is_closed ? 'Perbarui Laporan EOD' : 'Kirim Laporan EOD' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
