@extends('layouts.admin')

@section('page_title', 'Laporan Transaksi')

@section('admin_content')
@if(session('success'))
<div class="mb-4 p-4 rounded-xl bg-green-50 dark:bg-emerald-500/10 border border-green-200 dark:border-emerald-500/25 text-green-700 dark:text-emerald-300 text-sm flex items-center justify-between">
    <div class="flex items-center">
        <svg class="w-5 h-5 mr-3 text-green-500 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span>{{ session('success') }}</span>
    </div>
</div>
@endif

@if(session('error'))
<div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/25 text-red-700 dark:text-red-300 text-sm flex items-center justify-between">
    <div class="flex items-center">
        <svg class="w-5 h-5 mr-3 text-red-500 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span>{{ session('error') }}</span>
    </div>
</div>
@endif

<div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
    <div class="p-6">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-gray-500 dark:text-zinc-400 uppercase bg-gray-50 dark:bg-zinc-800/70">
                <tr>
                    <th class="px-4 py-3 rounded-l-lg">No. Trx</th>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Nasabah</th>
                    <th class="px-4 py-3">Jenis Transaksi</th>
                    <th class="px-4 py-3">Kasir</th>
                    <th class="px-4 py-3 text-right">Nominal</th>
                    <th class="px-4 py-3 text-right">Biaya Admin</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3 text-center rounded-r-lg">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $trx)
                <tr class="border-b dark:border-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors">
                    <td class="px-4 py-3 font-semibold text-gray-700 dark:text-zinc-300">#{{ $trx->transaction_number }}</td>
                    <td class="px-4 py-3">{{ $trx->transaction_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-800 dark:text-zinc-100">{{ $trx->customer->customer_name ?? '-' }}</div>
                        <div class="text-xs text-gray-400 dark:text-zinc-500 font-mono">{{ $trx->customer->customer_identifier ?? '-' }}</div>
                    </td>
                    <td class="px-4 py-3">{{ $trx->transactionType->name }}</td>
                    <td class="px-4 py-3">{{ $trx->cashier->full_name }}</td>
                    <td class="px-4 py-3 text-right">Rp {{ number_format($trx->amount, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right">Rp {{ number_format($trx->admin_fee, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">Rp {{ number_format($trx->total_payment, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-center">
                        <form method="POST" action="{{ route('admin.transactions.destroy', $trx->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi #{{ $trx->transaction_number }} ({{ $trx->customer->customer_name ?? 'Nasabah' }})? Data yang dihapus tidak dapat dikembalikan.')" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 bg-red-50 dark:bg-red-500/10 hover:bg-red-100 dark:hover:bg-red-500/20 border border-red-200 dark:border-red-500/30 px-3 py-1 rounded-md font-medium transition-colors cursor-pointer">
                                Hapus
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-zinc-500">Belum ada data transaksi</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="mt-4">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
