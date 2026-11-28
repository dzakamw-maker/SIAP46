@extends('layouts.app')

@section('content')
<div class="flex h-screen bg-gray-50 dark:bg-zinc-950 transition-colors duration-200">
    
    <!-- Sidebar -->
    <aside class="w-64 bg-orange-600 dark:bg-orange-800 text-white flex flex-col shadow-lg z-10 dark:border-r dark:border-orange-900/60">
        <a href="{{ url('/') }}" class="h-16 flex items-center justify-center border-b border-orange-500 dark:border-white/10">
            <h1 class="text-xl font-bold flex items-center text-white">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                Kasir Agen BNI
            </h1>
        </a>
        <div class="p-4 bg-orange-700 dark:bg-orange-900/70 dark:border-b dark:border-white/10">
            <p class="text-xs text-orange-200">Kasir Aktif</p>
            <p class="font-medium">{{ auth()->user()->full_name }}</p>
        </div>
        <nav class="flex-1 px-4 py-4 space-y-2">
            <a href="{{ url('/') }}" class="flex items-center px-4 py-3 {{ request()->is('/') || request()->routeIs('kasir.dashboard') ? 'bg-white text-orange-600 dark:bg-white/20 dark:text-white dark:ring-1 dark:ring-white/25' : 'text-white hover:bg-orange-500 dark:hover:bg-white/10' }} rounded-lg font-medium shadow-sm transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                Input Transaksi
            </a>
            <a href="{{ route('kasir.stamps') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('kasir.stamps') ? 'bg-white text-orange-600 dark:bg-white/20 dark:text-white dark:ring-1 dark:ring-white/25' : 'text-white hover:bg-orange-500 dark:hover:bg-white/10' }} rounded-lg font-medium shadow-sm transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                Restock Materai
            </a>
            <a href="{{ route('kasir.eod') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('kasir.eod') ? 'bg-white text-orange-600 dark:bg-white/20 dark:text-white dark:ring-1 dark:ring-white/25' : 'text-white hover:bg-orange-500 dark:hover:bg-white/10' }} rounded-lg font-medium shadow-sm transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                Rekap Harian (EOD)
            </a>
            <a href="{{ route('kasir.password') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('kasir.password*') ? 'bg-white text-orange-600 dark:bg-white/20 dark:text-white dark:ring-1 dark:ring-white/25' : 'text-white hover:bg-orange-500 dark:hover:bg-white/10' }} rounded-lg font-medium shadow-sm transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                Ganti Password
            </a>
        </nav>
        <div class="p-4">
            <button type="button" onclick="document.getElementById('logoutModal').classList.remove('hidden')" class="flex items-center justify-center px-4 py-2 bg-orange-700 hover:bg-orange-800 dark:bg-orange-900/75 dark:hover:bg-orange-950/80 text-white rounded transition-colors text-sm w-full cursor-pointer">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Keluar / Tutup Shift
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden relative">
        <header class="h-16 bg-white dark:bg-zinc-900 border-b border-gray-200 dark:border-zinc-800 flex items-center px-6 justify-between shadow-sm z-0 transition-colors duration-200">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-zinc-100">@yield('page_title', 'Workspace Kasir')</h2>
            <div class="flex items-center space-x-4">
                <x-theme-toggle class="p-2" />
                <div class="text-right">
                    <p class="text-xs text-gray-500 dark:text-zinc-400">Tanggal</p>
                    <p class="text-sm font-medium text-gray-800 dark:text-zinc-200">{{ date('d F Y') }}</p>
                </div>
            </div>
        </header>
        
        <div class="flex-1 overflow-auto p-6">
            @yield('cashier_content')
        </div>
    </main>
</div>

<!-- Logout Modal -->
<div id="logoutModal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/40 dark:bg-black/70 backdrop-blur-sm transition-opacity">
    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-lg p-6 max-w-sm w-full mx-4 border border-transparent dark:border-zinc-800">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-500/15 mb-4">
                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-zinc-100 mb-2">Konfirmasi Keluar</h3>
            <p class="text-sm text-gray-500 dark:text-zinc-400 mb-6">Apakah Anda yakin ingin keluar dan menutup shift kasir?</p>
            <div class="flex justify-center space-x-3">
                <button type="button" onclick="document.getElementById('logoutModal').classList.add('hidden')" class="px-4 py-2 bg-white dark:bg-zinc-800 border border-gray-300 dark:border-zinc-700 rounded-lg text-sm font-medium text-gray-700 dark:text-zinc-200 hover:bg-gray-50 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 cursor-pointer">
                    Batal
                </button>
                <form action="{{ route('logout') }}" method="POST" class="inline-block">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 cursor-pointer">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
