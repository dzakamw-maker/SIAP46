@extends('layouts.admin')

@section('page_title', 'Dashboard')

@section('admin_content')
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-zinc-800">
                    <p class="text-sm font-medium text-gray-500 dark:text-zinc-400 mb-1">Total Transaksi Hari Ini</p>
                    <h3 class="text-2xl font-bold text-gray-800 dark:text-zinc-100">{{ $todayTransactionsCount }}</h3>
                </div>
                <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-zinc-800">
                    <p class="text-sm font-medium text-gray-500 dark:text-zinc-400 mb-1">Total Nominal</p>
                    <h3 class="text-2xl font-bold text-gray-800 dark:text-zinc-100">Rp {{ number_format($todayTotalNominal, 0, ',', '.') }}</h3>
                </div>
                <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-zinc-800">
                    <p class="text-sm font-medium text-gray-500 dark:text-zinc-400 mb-1">Sisa Saldo BNI</p>
                    <h3 class="text-2xl font-bold text-orange-600 dark:text-orange-400">Rp {{ number_format($latestBniBalance, 0, ',', '.') }}</h3>
                </div>
                <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-zinc-800">
                    <p class="text-sm font-medium text-gray-500 dark:text-zinc-400 mb-1">Kasir Hadir</p>
                    <h3 class="text-2xl font-bold text-teal-600 dark:text-teal-400">{{ $presentCashiersCount }} / {{ $maxCashiersCount ?? 3 }} Siswa</h3>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Transactions -->
                <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center">
                        <h3 class="font-semibold text-gray-800 dark:text-zinc-100">Transaksi Terbaru</h3>
                        <a href="{{ route('admin.transactions') }}" class="text-sm text-teal-600 dark:text-teal-400 hover:underline">Lihat Semua</a>
                    </div>
                    <div class="p-6">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-500 dark:text-zinc-400 uppercase bg-gray-50 dark:bg-zinc-800/70">
                                <tr>
                                    <th class="px-4 py-2 rounded-l-lg">Nasabah</th>
                                    <th class="px-4 py-2">Jenis</th>
                                    <th class="px-4 py-2">Nominal</th>
                                    <th class="px-4 py-2 rounded-r-lg">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $trx)
                                    @php
                                        $typeCode = strtolower($trx->transactionType?->code ?? '');
                                        $badgeClass = match(true) {
                                            str_contains($typeCode, 'setor') => 'bg-blue-100 text-blue-800 dark:bg-blue-500/15 dark:text-blue-300',
                                            str_contains($typeCode, 'tarik') => 'bg-purple-100 text-purple-800 dark:bg-purple-500/15 dark:text-purple-300',
                                            str_contains($typeCode, 'materai') => 'bg-orange-100 text-orange-800 dark:bg-orange-500/15 dark:text-orange-300',
                                            str_contains($typeCode, 'pln') => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-500/15 dark:text-yellow-300',
                                            str_contains($typeCode, 'dana') || str_contains($typeCode, 'gopay') || str_contains($typeCode, 'ovo') || str_contains($typeCode, 'shopee') => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
                                            default => 'bg-gray-100 text-gray-800 dark:bg-zinc-800 dark:text-zinc-300',
                                        };
                                    @endphp
                                    <tr class="border-b dark:border-zinc-800 last:border-b-0 hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors">
                                        <td class="px-4 py-3 font-medium text-gray-800 dark:text-zinc-100">
                                            {{ $trx->customer?->customer_name ?? $trx->customer?->customer_identifier ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="{{ $badgeClass }} text-xs px-2.5 py-0.5 rounded font-medium">
                                                {{ $trx->transactionType?->name ?? 'Transaksi' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-800 dark:text-zinc-100 font-medium">
                                            Rp {{ number_format($trx->amount, 0, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3 text-green-600 dark:text-emerald-400 font-medium">
                                            Selesai
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-400 dark:text-zinc-500">
                                            Belum ada data transaksi
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Presensi Kasir -->
                <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center">
                        <h3 class="font-semibold text-gray-800 dark:text-zinc-100">Presensi Kasir Hari Ini</h3>
                        <a href="{{ route('admin.attendance') }}" class="text-sm text-teal-600 dark:text-teal-400 hover:underline">Lihat Semua</a>
                    </div>
                    <div class="p-6 space-y-4">
                        @forelse($todayAttendances as $att)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-teal-100 dark:bg-teal-500/20 flex items-center justify-center text-teal-800 dark:text-teal-300 font-bold">
                                        {{ strtoupper(substr($att->user?->full_name ?? 'K', 0, 2)) }}
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900 dark:text-zinc-100">{{ $att->user?->full_name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-zinc-400">{{ $att->student_note ?? 'Hadir' }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center bg-green-100 dark:bg-emerald-500/15 text-green-800 dark:text-emerald-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                    <span class="w-2 h-2 mr-1 bg-green-500 rounded-full"></span>
                                    Hadir
                                </span>
                            </div>
                        @empty
                            <p class="text-center text-sm text-gray-400 dark:text-zinc-500 py-4">Belum ada kasir yang presensi hari ini</p>
                        @endforelse
                    </div>
                </div>
            </div>
@endsection
