@extends('layouts.cashier')

@section('page_title', 'Restock Materai')

@section('cashier_content')
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
        <span>Gagal menyimpan restock materai:</span>
    </div>
    <ul class="list-disc list-inside space-y-1 text-xs text-red-600 dark:text-red-400 ml-7">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Form Restock Materai -->
    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-200 dark:border-zinc-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-800/50">
            <h3 class="font-semibold text-gray-800 dark:text-zinc-100 text-lg">Catat Restock Materai Baru</h3>
        </div>
        <div class="p-6">
            <form action="{{ route('kasir.stamps.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="record_date" class="block mb-2 text-sm font-medium text-gray-700 dark:text-zinc-300">Tanggal <span class="text-red-500">*</span></label>
                    <input type="date" id="record_date" name="record_date" value="{{ old('record_date', date('Y-m-d')) }}" required class="bg-gray-50 dark:bg-zinc-950/60 border border-gray-300 dark:border-zinc-700 text-gray-900 dark:text-zinc-100 text-sm rounded-lg focus:ring-orange-500 focus:border-orange-500 block w-full p-2.5">
                </div>
                <div class="mb-4">
                    <label for="quantity_purchased" class="block mb-2 text-sm font-medium text-gray-700 dark:text-zinc-300">Jumlah Materai Ditambahkan (pcs) <span class="text-red-500">*</span></label>
                    <input type="number" id="quantity_purchased" name="quantity_purchased" value="{{ old('quantity_purchased') }}" min="1" required class="bg-gray-50 dark:bg-zinc-950/60 border border-gray-300 dark:border-zinc-700 text-gray-900 dark:text-zinc-100 dark:placeholder-zinc-500 text-sm rounded-lg focus:ring-orange-500 focus:border-orange-500 block w-full p-2.5" placeholder="Contoh: 50">
                </div>
                <div class="mb-4">
                    <label for="amount" class="block mb-2 text-sm font-medium text-gray-700 dark:text-zinc-300">Total Harga Beli (Rp)</label>
                    <input type="number" id="amount" name="amount" value="{{ old('amount') }}" min="0" step="any" class="bg-gray-50 dark:bg-zinc-950/60 border border-gray-300 dark:border-zinc-700 text-gray-900 dark:text-zinc-100 dark:placeholder-zinc-500 text-sm rounded-lg focus:ring-orange-500 focus:border-orange-500 block w-full p-2.5" placeholder="Contoh: 500000">
                    <span class="text-xs text-gray-400 dark:text-zinc-500 mt-1 block">Acuan harga modal saat ini: Rp {{ number_format($stampCostPrice ?? 10000, 0, ',', '.') }} / pcs</span>
                </div>
                <div class="mb-6">
                    <label for="notes" class="block mb-2 text-sm font-medium text-gray-700 dark:text-zinc-300">Catatan Tambahan (Opsional)</label>
                    <textarea id="notes" name="notes" class="bg-gray-50 dark:bg-zinc-950/60 border border-gray-300 dark:border-zinc-700 text-gray-900 dark:text-zinc-100 dark:placeholder-zinc-500 text-sm rounded-lg focus:ring-orange-500 focus:border-orange-500 block w-full p-2.5" rows="2" placeholder="Contoh: Pembelian materai di Kantor Pos">{{ old('notes') }}</textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="text-white bg-orange-600 hover:bg-orange-700 focus:ring-4 focus:outline-none focus:ring-orange-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center cursor-pointer transition-colors">Simpan Restock</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Info Stok Materai -->
    <div class="space-y-6">
        <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-200 dark:border-zinc-800 p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-zinc-400 mb-1">Informasi Stok Materai</h3>
            <div class="flex justify-between items-end mb-4">
                <p class="text-3xl font-bold text-gray-800 dark:text-zinc-100">{{ $stampsStock }} <span class="text-lg font-normal text-gray-500 dark:text-zinc-400">pcs</span></p>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-200 dark:border-zinc-800 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-800/50 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 dark:text-zinc-100">Riwayat Restock Terbaru</h3>
                <span class="text-xs text-gray-500 dark:text-zinc-400 font-medium">{{ count($recentRestocks) }} Catatan</span>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-zinc-800 max-h-96 overflow-y-auto">
                @forelse($recentRestocks as $record)
                    <div class="p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 dark:bg-emerald-500/15 text-emerald-800 dark:text-emerald-300">
                                    +{{ $record->quantity_purchased }} pcs
                                </span>
                                <span class="text-xs text-gray-500 dark:text-zinc-400">
                                    {{ \Carbon\Carbon::parse($record->record_date)->translatedFormat('d M Y') }}
                                </span>
                            </div>
                            <p class="text-sm font-medium text-gray-800 dark:text-zinc-100 mt-1">
                                {{ $record->notes ?: 'Restock Materai' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-zinc-400">
                                Oleh: {{ $record->cashier?->full_name ?? 'Petugas' }}
                            </p>
                        </div>
                        <div class="text-right">
                            @if($record->amount > 0)
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    Rp {{ number_format($record->amount, 0, ',', '.') }}
                                </p>
                            @endif
                            <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">
                                Sisa: <span class="font-medium text-gray-700 dark:text-zinc-200">{{ $record->remaining_stock }} pcs</span>
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-sm text-gray-500 dark:text-zinc-500">Belum ada riwayat restock materai</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
