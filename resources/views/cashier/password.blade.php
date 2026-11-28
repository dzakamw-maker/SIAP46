@extends('layouts.cashier')

@section('page_title', 'Pengaturan Akun & Ganti Password')

@section('cashier_content')
<div class="max-w-2xl mx-auto space-y-6">
    @if(session('success'))
    <div class="p-4 rounded-xl bg-green-50 dark:bg-emerald-500/10 border border-green-200 dark:border-emerald-500/25 text-green-700 dark:text-emerald-300 text-sm flex items-center justify-between shadow-sm">
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-3 text-green-500 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/25 text-red-700 dark:text-red-300 text-sm shadow-sm">
        <div class="flex items-center mb-1 font-medium">
            <svg class="w-5 h-5 mr-2 text-red-500 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>Gagal memperbarui password:</span>
        </div>
        <ul class="list-disc list-inside space-y-1 text-xs text-red-600 dark:text-red-400 ml-7">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Kartu Informasi Akun Profil (Read-Only) -->
    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
        <div class="p-5 border-b border-gray-100 dark:border-zinc-800 flex items-center justify-between bg-gray-50/50 dark:bg-zinc-800/50">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-orange-100 dark:bg-orange-500/15 text-orange-600 dark:text-orange-400 flex items-center justify-center font-bold text-lg">
                    {{ strtoupper(substr($user->full_name, 0, 1)) }}
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-800 dark:text-zinc-100">{{ $user->full_name }}</h3>
                    <p class="text-xs text-gray-500 dark:text-zinc-400">Role: <span class="font-semibold text-orange-600 dark:text-orange-400">{{ $user->role?->name ?? 'Kasir' }}</span></p>
                </div>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-500/15 text-emerald-800 dark:text-emerald-300">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                Akun Aktif
            </span>
        </div>

        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1">
                    Username
                </label>
                <div class="relative">
                    <input type="text" value="{{ $user->username }}" readonly disabled
                        class="w-full bg-gray-100 dark:bg-zinc-800/70 border border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-zinc-300 rounded-lg p-2.5 pl-10 font-mono text-sm cursor-not-allowed select-all">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                </div>
                <p class="text-[11px] text-gray-400 dark:text-zinc-500 mt-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Username terkunci dan tidak dapat diubah.
                </p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1">
                    NIS / Nomor Induk
                </label>
                <input type="text" value="{{ $user->student_number ?? '-' }}" readonly disabled
                    class="w-full bg-gray-100 dark:bg-zinc-800/70 border border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-zinc-300 rounded-lg p-2.5 font-mono text-sm cursor-not-allowed">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1">
                    Kelas / Rombel
                </label>
                <input type="text" value="{{ $user->class_group ?? '-' }}" readonly disabled
                    class="w-full bg-gray-100 dark:bg-zinc-800/70 border border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-zinc-300 rounded-lg p-2.5 text-sm cursor-not-allowed">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1">
                    Terdaftar Sejak
                </label>
                <input type="text" value="{{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}" readonly disabled
                    class="w-full bg-gray-100 dark:bg-zinc-800/70 border border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-zinc-300 rounded-lg p-2.5 text-sm cursor-not-allowed">
            </div>
        </div>
    </div>

    <!-- Kartu Form Ganti Password -->
    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
        <div class="p-5 border-b border-gray-100 dark:border-zinc-800 bg-orange-50/40 dark:bg-orange-500/10 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-orange-100 dark:bg-orange-500/20 text-orange-600 dark:text-orange-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-800 dark:text-zinc-100">Perbarui Kata Sandi</h3>
                    <p class="text-xs text-gray-500 dark:text-zinc-400">Gunakan minimal 6 karakter kombinasi yang mudah Anda ingat.</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('kasir.password.update') }}" class="p-6 space-y-5">
            @csrf
            @method('PUT')

            <!-- Password Baru -->
            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-zinc-300 mb-1.5">
                    Password Baru <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="password" name="password" id="password" required minlength="6"
                        placeholder="Masukkan password baru (minimal 6 karakter)"
                        class="w-full rounded-lg bg-white dark:bg-zinc-950/60 border border-gray-300 dark:border-zinc-700 text-gray-900 dark:text-zinc-100 dark:placeholder-zinc-500 focus:border-orange-500 focus:ring-1 focus:ring-orange-500 p-2.5 text-sm font-mono pr-10 transition-colors">
                    <button type="button" onclick="togglePasswordVisibility('password', this)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-zinc-200 focus:outline-none cursor-pointer">
                        <svg class="w-5 h-5 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Konfirmasi Password Baru -->
            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 dark:text-zinc-300 mb-1.5">
                    Konfirmasi Password Baru <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="password" name="password_confirmation" id="password_confirmation" required minlength="6"
                        placeholder="Ulangi password baru yang sama"
                        class="w-full rounded-lg bg-white dark:bg-zinc-950/60 border border-gray-300 dark:border-zinc-700 text-gray-900 dark:text-zinc-100 dark:placeholder-zinc-500 focus:border-orange-500 focus:ring-1 focus:ring-orange-500 p-2.5 text-sm font-mono pr-10 transition-colors">
                    <button type="button" onclick="togglePasswordVisibility('password_confirmation', this)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-zinc-200 focus:outline-none cursor-pointer">
                        <svg class="w-5 h-5 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="pt-3 border-t border-gray-100 dark:border-zinc-800 flex items-center justify-end">
                <button type="submit"
                    class="bg-orange-600 hover:bg-orange-700 text-white font-semibold px-5 py-2.5 rounded-lg text-sm shadow-sm transition-colors flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Simpan Password Baru
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function togglePasswordVisibility(fieldId, button) {
    const input = document.getElementById(fieldId);
    if (!input) return;

    if (input.type === 'password') {
        input.type = 'text';
        button.innerHTML = `
            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
            </svg>
        `;
    } else {
        input.type = 'password';
        button.innerHTML = `
            <svg class="w-5 h-5 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
        `;
    }
}
</script>
@endsection
