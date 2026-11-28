@extends('layouts.admin')

@section('page_title', 'Rekap Harian (End of Day)')

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

<div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden mb-6">
    <div class="p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
            <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-zinc-100">Pengaturan Harga Materai</h3>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">Atur harga jual standar di loket kasir dan harga beli (modal) untuk valuasi aset EOD.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.settings.stamp-prices') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            @csrf
            <div>
                <label for="stamp_selling_price" class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">Harga Jual Materai (Rp / pcs)</label>
                <input type="number" name="stamp_selling_price" id="stamp_selling_price" min="0" step="1" required value="{{ old('stamp_selling_price', $stampSellingPrice) }}" class="w-full text-sm rounded-lg bg-white dark:bg-zinc-950/60 border-gray-300 dark:border-zinc-700 text-gray-900 dark:text-zinc-100 shadow-sm focus:border-teal-500 focus:ring-teal-500 p-2.5 border font-mono">
            </div>
            <div>
                <label for="stamp_cost_price" class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">Harga Beli / Modal (Rp / pcs)</label>
                <input type="number" name="stamp_cost_price" id="stamp_cost_price" min="0" step="1" required value="{{ old('stamp_cost_price', $stampCostPrice) }}" class="w-full text-sm rounded-lg bg-white dark:bg-zinc-950/60 border-gray-300 dark:border-zinc-700 text-gray-900 dark:text-zinc-100 shadow-sm focus:border-teal-500 focus:ring-teal-500 p-2.5 border font-mono">
            </div>
            <div>
                <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-medium shadow-sm transition-colors cursor-pointer">
                    Simpan Harga Materai
                </button>
            </div>
        </form>
    </div>
</div>

<div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
    <div class="p-6">
        <div class="mb-5">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-zinc-100">Laporan Rekonsiliasi Kasir (EOD)</h3>
            <p class="text-sm text-gray-500 dark:text-zinc-400 mt-1">Periksa kesesuaian uang tunai di laci, saldo mutasi BNI, dan sisa materai dari kasir siswa.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 dark:text-zinc-400 uppercase bg-gray-50 dark:bg-zinc-800/70">
                    <tr>
                        <th class="px-4 py-3 rounded-l-lg">Tanggal</th>
                        <th class="px-4 py-3">Petugas Kasir</th>
                        <th class="px-4 py-3 text-right">Saldo BNI Aktual</th>
                        <th class="px-4 py-3 text-right">Uang Kas Fisik</th>
                        <th class="px-4 py-3 text-center">Sisa Materai</th>
                        <th class="px-4 py-3">Catatan Kasir</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center rounded-r-lg">Aksi Guru</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recaps as $recap)
                    <tr class="border-b dark:border-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-zinc-100 whitespace-nowrap">
                            {{ $recap->recap_date ? $recap->recap_date->format('d/m/Y') : '-' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="font-medium text-gray-800 dark:text-zinc-100">{{ $recap->recordedBy->full_name ?? 'Kasir Umum' }}</div>
                            <div class="text-xs text-gray-400 dark:text-zinc-500">{{ $recap->recordedBy->class_group ?? '-' }}</div>
                        </td>
                        <td class="px-4 py-3 text-right font-medium whitespace-nowrap">
                            Rp {{ number_format($recap->bni_balance_remaining ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-gray-800 dark:text-zinc-100 whitespace-nowrap">
                            Rp {{ number_format($recap->cash_amount ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            <span class="bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 text-xs px-2.5 py-1 rounded-full font-medium">
                                {{ $recap->stamp_remaining_value ?? 0 }} pcs
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-zinc-300 max-w-xs truncate" title="{{ $recap->notes }}">
                            {{ $recap->notes ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if($recap->verified_by)
                                <span class="inline-flex items-center text-xs font-medium bg-green-100 dark:bg-emerald-500/15 text-green-800 dark:text-emerald-300 px-2.5 py-1 rounded-full">
                                    <svg class="w-3.5 h-3.5 mr-1 text-green-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Terverifikasi
                                </span>
                                <div class="text-xs text-gray-400 dark:text-zinc-500 mt-0.5">oleh {{ $recap->verifiedBy->full_name ?? 'Guru' }}</div>
                            @else
                                <span class="inline-flex items-center text-xs font-medium bg-amber-100 dark:bg-amber-500/15 text-amber-800 dark:text-amber-300 px-2.5 py-1 rounded-full">
                                    <svg class="w-3.5 h-3.5 mr-1 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Menunggu Verifikasi
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if($recap->verified_by)
                                <span class="text-xs text-gray-400 dark:text-zinc-500 italic">Sudah Disetujui</span>
                            @else
                                <button type="button" onclick="openVerifyModal('{{ $recap->id }}', '{{ $recap->recap_date->format('d/m/Y') }}', '{{ $recap->recordedBy->full_name ?? 'Kasir' }}', '{{ number_format($recap->cash_amount ?? 0, 0, ',', '.') }}', '{{ number_format($recap->bni_balance_remaining ?? 0, 0, ',', '.') }}')" class="inline-flex items-center px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-medium rounded-lg transition-colors shadow-sm cursor-pointer">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Verifikasi
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-zinc-500">Belum ada data Rekap Harian (EOD) yang dikirim oleh kasir.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $recaps->links() }}
        </div>
    </div>
</div>

<!-- Modal Verifikasi Rekap Harian -->
<div id="verifyModal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/40 dark:bg-black/70 backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-xl p-6 max-w-md w-full mx-4 border border-transparent dark:border-zinc-800">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-zinc-800 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-zinc-100 flex items-center">
                <svg class="w-5 h-5 text-teal-600 dark:text-teal-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Verifikasi Laporan EOD
            </h3>
            <button type="button" onclick="closeVerifyModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-zinc-200 cursor-pointer">&times;</button>
        </div>

        <form id="verifyForm" method="POST" action="">
            @csrf
            
            <div class="bg-gray-50 dark:bg-zinc-800/70 rounded-lg p-3 text-xs text-gray-600 dark:text-zinc-300 space-y-1.5 mb-4 border border-gray-100 dark:border-zinc-700">
                <div class="flex justify-between">
                    <span class="text-gray-400">Tanggal Rekap:</span>
                    <span id="modalDate" class="font-medium text-gray-800 dark:text-zinc-100"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Petugas Kasir:</span>
                    <span id="modalCashier" class="font-medium text-gray-800 dark:text-zinc-100"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Uang Kas Fisik:</span>
                    <span class="font-semibold text-gray-900 dark:text-white">Rp <span id="modalCash"></span></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Saldo BNI Aktual:</span>
                    <span class="font-semibold text-gray-900 dark:text-white">Rp <span id="modalBalance"></span></span>
                </div>
            </div>

            <div class="mb-4">
                <label for="teacher_note" class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">Catatan Evaluasi Guru (Opsional)</label>
                <textarea name="teacher_note" id="teacher_note" rows="2" class="w-full text-xs rounded-lg bg-white dark:bg-zinc-950/60 border-gray-300 dark:border-zinc-700 text-gray-900 dark:text-zinc-100 dark:placeholder-zinc-500 shadow-sm focus:border-teal-500 focus:ring-teal-500 p-2.5 border" placeholder="Contoh: Kas fisik dan saldo klop, kerja bagus."></textarea>
            </div>

            <div class="flex justify-end space-x-2 pt-2">
                <button type="button" onclick="closeVerifyModal()" class="px-4 py-2 bg-white dark:bg-zinc-800 border border-gray-300 dark:border-zinc-700 rounded-lg text-xs font-medium text-gray-700 dark:text-zinc-200 hover:bg-gray-50 dark:hover:bg-zinc-700 cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-xs font-medium shadow-sm transition-colors flex items-center cursor-pointer">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Setujui & Verifikasi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openVerifyModal(id, date, cashier, cash, balance) {
        document.getElementById('modalDate').innerText = date;
        document.getElementById('modalCashier').innerText = cashier;
        document.getElementById('modalCash').innerText = cash;
        document.getElementById('modalBalance').innerText = balance;
        document.getElementById('verifyForm').action = '/admin/eod/' + id + '/verify';
        document.getElementById('verifyModal').classList.remove('hidden');
    }

    function closeVerifyModal() {
        document.getElementById('verifyModal').classList.add('hidden');
    }
</script>
@endsection
