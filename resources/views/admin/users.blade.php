@extends('layouts.admin')

@section('page_title', 'Kelola User')

@section('admin_content')
@if(session('error'))
<div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm flex items-center justify-between">
    <div class="flex items-center">
        <svg class="w-5 h-5 mr-3 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span>{{ session('error') }}</span>
    </div>
</div>
@endif

@if(session('success'))
<div class="mb-4 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm flex items-center justify-between">
    <div class="flex items-center">
        <svg class="w-5 h-5 mr-3 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span>{{ session('success') }}</span>
    </div>
</div>
@endif

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <!-- Form Pencarian & Dropdown Filter (Kompak & Pendek) -->
    <form method="GET" action="{{ route('admin.users') }}" id="userFilterForm" class="flex flex-wrap items-center gap-2">
        <!-- Kolom Ketik Search (Pendek / Kompak) -->
        <div class="relative flex items-center" style="width: 210px;">
            <span class="pointer-events-none text-gray-400 flex items-center justify-center" style="position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%); width: 1rem; height: 1rem; z-index: 10;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 1rem; height: 1rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </span>
            <input 
                type="text" 
                id="searchUserInput"
                name="search" 
                value="{{ $search ?? '' }}" 
                placeholder="Cari user / NIS..." 
                class="w-full text-sm bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors shadow-sm"
                style="padding-left: 2.25rem !important; padding-right: 2rem !important; padding-top: 0.5rem; padding-bottom: 0.5rem;"
                autocomplete="off"
            >
            @if(!empty($search))
                <a href="{{ route('admin.users', array_filter(['role' => $selectedRole ?? null, 'status' => $selectedStatus ?? null])) }}" class="text-gray-400 hover:text-gray-600 flex items-center justify-center" style="position: absolute; right: 0.65rem; top: 50%; transform: translateY(-50%); width: 1rem; height: 1rem; z-index: 10;" title="Hapus teks pencarian">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 0.875rem; height: 0.875rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>
            @endif
        </div>

        <!-- Dropdown Filter Role (Kompak) -->
        <select 
            name="role" 
            id="roleFilterSelect"
            onchange="this.form.submit()" 
            class="w-auto py-2 pl-3 pr-8 text-sm bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors shadow-sm text-gray-700 cursor-pointer"
        >
            <option value="">Semua Role</option>
            @foreach($roles as $role)
                <option value="{{ $role->name }}" {{ ($selectedRole ?? '') === $role->name ? 'selected' : '' }}>
                    {{ $role->name }}
                </option>
            @endforeach
        </select>

        <!-- Dropdown Filter Status (Kompak) -->
        <select 
            name="status" 
            id="statusFilterSelect"
            onchange="this.form.submit()" 
            class="w-auto py-2 pl-3 pr-8 text-sm bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors shadow-sm text-gray-700 cursor-pointer"
        >
            <option value="">Semua Status</option>
            <option value="1" {{ ($selectedStatus ?? '') === '1' ? 'selected' : '' }}>Aktif</option>
            <option value="0" {{ ($selectedStatus ?? '') === '0' ? 'selected' : '' }}>Nonaktif</option>
        </select>

        @if(!empty($search) || !empty($selectedRole) || ($selectedStatus !== null && $selectedStatus !== ''))
            <a href="{{ route('admin.users') }}" class="text-xs text-red-600 hover:text-red-700 hover:underline font-medium inline-flex items-center gap-1 px-2 py-1" title="Reset filter">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                Reset
            </a>
        @endif
    </form>

    <div class="flex items-center gap-2 shrink-0">
        <!-- Tombol Hapus Semua Kasir -->
        @if(($cashierCount ?? 0) > 0)
        <button 
            type="button" 
            onclick="openDeleteAllCashiersModal()" 
            class="w-auto bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 px-3 py-2 rounded-lg text-sm font-medium inline-flex items-center shadow-sm whitespace-nowrap cursor-pointer transition-colors"
            title="Hapus seluruh user dengan role Kasir ({{ $cashierCount }} user)"
        >
            <svg class="w-4 h-4 mr-1.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Hapus Semua Kasir ({{ $cashierCount }})
        </button>
        @endif

        <!-- Tombol Import Siswa (Excel / CSV) -->
        <button type="button" onclick="openImportModal()" class="w-auto bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-2 rounded-lg text-sm font-medium inline-flex items-center shadow-sm whitespace-nowrap cursor-pointer transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            Import Siswa
        </button>

        <!-- Tombol Tambah User (Kompak / W-Auto) -->
        <a href="{{ route('admin.users.create') }}" class="w-auto bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium inline-flex items-center shadow-sm whitespace-nowrap transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Tambah User Baru
        </a>
    </div>
</div>

@php
    $hasActiveFilter = !empty($search) || !empty($selectedRole) || ($selectedStatus !== null && $selectedStatus !== '');
@endphp

@if($hasActiveFilter)
<div class="mb-3 text-xs text-gray-500 flex flex-wrap items-center justify-between gap-2">
    <span>
        Menampilkan <strong>{{ $users->count() }}</strong> user
        @if(!empty($search)) dengan kata kunci "<strong class="text-gray-800">{{ $search }}</strong>"@endif
        @if(!empty($selectedRole)) [Role: <strong class="text-gray-800">{{ $selectedRole }}</strong>]@endif
        @if($selectedStatus !== null && $selectedStatus !== '') [Status: <strong class="text-gray-800">{{ $selectedStatus === '1' ? 'Aktif' : 'Nonaktif' }}</strong>]@endif
    </span>
    <a href="{{ route('admin.users') }}" class="text-blue-600 hover:underline font-medium">Tampilkan Semua</a>
</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 rounded-l-lg">Nama Lengkap</th>
                        <th class="px-4 py-3">Username</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">NIS / Kelas</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 rounded-r-lg">Aksi</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    @forelse($users as $user)
                    <tr class="user-row border-b hover:bg-gray-50" data-role="{{ $user->role?->name ?? '' }}" data-status="{{ $user->is_active ? '1' : '0' }}">
                        <td class="px-4 py-3 font-medium">
                            {{ $user->full_name }}
                            @if($user->id === auth()->id())
                                <span class="ml-1 text-xs text-teal-700 bg-teal-50 px-2 py-0.5 rounded font-normal border border-teal-200">Anda</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $user->username }}</td>
                        <td class="px-4 py-3">
                            <span class="{{ ($user->role?->name ?? '') == 'Admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }} text-xs px-2.5 py-0.5 rounded">
                                {{ $user->role?->name ?? '-' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ $user->student_number ? $user->student_number . ' / ' . $user->class_group : '-' }}</td>
                        <td class="px-4 py-3">
                            @if($user->is_active)
                                <span class="text-green-600 font-medium">Aktif</span>
                            @else
                                <span class="text-red-600 font-medium">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 flex items-center space-x-3">
                            <a href="{{ route('admin.users.edit', $user->id) }}" class="text-blue-600 hover:underline font-medium">Edit</a>
                            @if($user->id !== auth()->id())
                                <a href="{{ route('admin.users.delete', $user->id) }}" class="text-red-600 hover:underline font-medium">Hapus</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr id="serverEmptyRow">
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            @if($hasActiveFilter)
                                <p class="font-medium text-gray-700">Tidak ada user yang cocok dengan filter yang dipilih.</p>
                                <a href="{{ route('admin.users') }}" class="mt-2 inline-block text-xs text-blue-600 hover:underline font-medium">Reset Filter</a>
                            @else
                                <p class="font-medium text-gray-700">Belum ada data user.</p>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                    <tr id="clientEmptyRow" style="display: none;">
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            <p class="font-medium text-gray-700">Tidak ada user yang cocok pada tampilan ini.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Import Siswa (Excel / CSV) -->
<div id="importStudentModal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/50 backdrop-blur-xs transition-opacity p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-100 animate-in fade-in duration-200">
        <div class="px-6 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold">Import Data Siswa</h3>
                    <p class="text-xs text-emerald-100">Upload file Excel / CSV ke tabel user dengan role Kasir</p>
                </div>
            </div>
            <button type="button" onclick="closeImportModal()" class="text-white/80 hover:text-white rounded-lg p-1.5 hover:bg-white/10 transition-colors cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('admin.users.import') }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf

            <!-- Template Download & Info Box -->
            <div class="p-3.5 bg-emerald-50 rounded-xl border border-emerald-200 text-xs text-emerald-900 mb-5">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-bold flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Format Kolom yang Didukung:
                    </span>
                    <a href="{{ route('admin.users.template') }}" class="font-bold text-emerald-700 hover:text-emerald-900 underline inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download Template (.csv)
                    </a>
                </div>
                <p class="text-gray-600 leading-relaxed">
                    Pastikan baris pertama berisi header: <strong>Nama Siswa</strong>, <strong>NIS</strong>, <strong>Kelas</strong>, <strong>Username</strong> <em>(opsional)</em>, <strong>Password</strong> <em>(opsional)</em>.
                </p>
                <div class="mt-2 pt-2 border-t border-emerald-200/60 text-[11px] text-emerald-800">
                    💡 <em>Semua siswa yang diimport otomatis mendapat role <strong>Kasir</strong> dan status <strong>Aktif</strong>. Username dan Password awal otomatis menggunakan nomor <strong>NIS</strong> siswa.</em>
                </div>
            </div>

            <!-- Upload File -->
            <div class="mb-4">
                <label for="import_file" class="block mb-2 text-sm font-semibold text-gray-800">
                    Pilih File Excel / CSV <span class="text-red-500">*</span>
                </label>
                <input type="file" name="file" id="import_file" required accept=".xlsx,.csv,.txt"
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                <span class="text-[11px] text-gray-400 mt-1 block">Mendukung format Microsoft Excel (.xlsx) atau CSV (.csv). Maksimal 5 MB.</span>
            </div>

            <!-- Default Password -->
            <div class="mb-6">
                <label for="default_password" class="block mb-1.5 text-sm font-semibold text-gray-800">
                    Password Cadangan <span class="text-xs font-normal text-gray-500">(Hanya digunakan jika baris siswa tidak memiliki NIS)</span>
                </label>
                <input type="text" name="default_password" id="default_password" placeholder="Opsional (minimal 6 karakter)"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 font-mono">
                <span class="text-[11px] text-gray-400 mt-1 block">Jika dikosongkan, siswa tanpa NIS akan menggunakan username-nya sebagai password awal.</span>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeImportModal()" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition-colors flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Mulai Import Siswa
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Semua Kasir -->
<div id="deleteAllCashiersModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl overflow-hidden transform transition-all border border-gray-100">
        <!-- Modal Header -->
        <div class="p-5 bg-red-50/80 border-b border-red-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Hapus Semua Kasir</h3>
                    <p class="text-xs text-red-600">Tindakan permanen dan tidak dapat dibatalkan</p>
                </div>
            </div>
            <button type="button" onclick="closeDeleteAllCashiersModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-white/80 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Modal Body & Form -->
        <form method="POST" action="{{ route('admin.users.delete-all-cashiers') }}" class="p-6">
            @csrf
            @method('DELETE')
            
            <div class="mb-5 text-sm text-gray-600 space-y-3">
                <p>
                    Apakah Anda yakin ingin menghapus <strong>seluruh user dengan role Kasir</strong> (total <span class="font-bold text-red-600">{{ $cashierCount ?? 0 }}</span> user)?
                </p>
                <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800 flex items-start gap-2">
                    <svg class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Akun <strong>Administrator</strong> dan user dengan role selain Kasir <strong>tetap aman dan tidak akan terhapus</strong>.</span>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeDeleteAllCashiersModal()" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm transition-colors flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Ya, Hapus Semua Kasir
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    window.openImportModal = function() {
        const modal = document.getElementById('importStudentModal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    };

    window.closeImportModal = function() {
        const modal = document.getElementById('importStudentModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    };

    window.openDeleteAllCashiersModal = function() {
        const modal = document.getElementById('deleteAllCashiersModal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    };

    window.closeDeleteAllCashiersModal = function() {
        const modal = document.getElementById('deleteAllCashiersModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        const importModal = document.getElementById('importStudentModal');
        if (importModal) {
            importModal.addEventListener('click', function(e) {
                if (e.target === importModal) {
                    closeImportModal();
                }
            });
        }

        const deleteCashiersModal = document.getElementById('deleteAllCashiersModal');
        if (deleteCashiersModal) {
            deleteCashiersModal.addEventListener('click', function(e) {
                if (e.target === deleteCashiersModal) {
                    closeDeleteAllCashiersModal();
                }
            });
        }

        const searchInput = document.getElementById('searchUserInput');
        const roleSelect = document.getElementById('roleFilterSelect');
        const statusSelect = document.getElementById('statusFilterSelect');
        const rows = document.querySelectorAll('.user-row');
        const clientEmpty = document.getElementById('clientEmptyRow');

        function filterRows() {
            const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
            const selectedRole = (roleSelect ? roleSelect.value : '').toLowerCase();
            const selectedStatus = statusSelect ? statusSelect.value : '';
            let visibleCount = 0;

            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                const rowRole = (row.getAttribute('data-role') || '').toLowerCase();
                const rowStatus = row.getAttribute('data-status') || '';

                const matchesQuery = (query === '' || text.includes(query));
                const matchesRole = (selectedRole === '' || rowRole === selectedRole);
                const matchesStatus = (selectedStatus === '' || rowStatus === selectedStatus);

                if (matchesQuery && matchesRole && matchesStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (clientEmpty) {
                clientEmpty.style.display = (visibleCount === 0 && (query !== '' || selectedRole !== '' || selectedStatus !== '')) ? '' : 'none';
            }
        }

        if (searchInput && rows.length > 0) {
            searchInput.addEventListener('input', filterRows);
        }
    });
</script>
@endsection
