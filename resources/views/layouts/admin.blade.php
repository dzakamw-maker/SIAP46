@extends('layouts.app')

@section('content')
<div class="flex h-screen overflow-hidden bg-gray-100 dark:bg-zinc-950 transition-colors duration-200">
    <!-- Mobile Sidebar Backdrop -->
    <div data-sidebar-backdrop onclick="window.toggleSidebar && window.toggleSidebar(false)" aria-hidden="true" class="fixed inset-0 z-30 bg-black/40 dark:bg-black/60 backdrop-blur-[1px] opacity-0 invisible pointer-events-none transition-all duration-200 md:hidden"></div>

    <!-- Sidebar -->
    <aside id="appSidebar" data-sidebar class="w-64 shrink-0 bg-teal-800 dark:bg-[#004750] text-white flex flex-col border-r border-transparent dark:border-teal-900/60 transition-all duration-200 ease-in-out">
        <div class="h-16 px-4 flex items-center justify-between md:justify-center border-b border-teal-700 dark:border-white/10 shrink-0">
            <a href="{{ url('/') }}" class="flex items-center">
                <h1 class="text-xl font-bold whitespace-nowrap">Agen BNI Admin</h1>
            </a>
            <button type="button" onclick="window.toggleSidebar && window.toggleSidebar(false)" aria-label="Tutup Sidebar" title="Tutup Sidebar" class="md:hidden p-1.5 rounded-lg text-teal-100 hover:text-white hover:bg-teal-700 dark:hover:bg-white/10 transition-colors cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
            <a href="{{ url('/') }}" class="flex items-center px-4 py-3 {{ request()->is('/') || request()->routeIs('admin.dashboard') ? 'bg-teal-900 dark:bg-white/15 text-white dark:ring-1 dark:ring-white/20' : 'hover:bg-teal-700 dark:hover:bg-white/10' }} rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Dashboard
            </a>
            <a href="{{ route('admin.transactions') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('admin.transactions') ? 'bg-teal-900 dark:bg-white/15 text-white dark:ring-1 dark:ring-white/20' : 'hover:bg-teal-700 dark:hover:bg-white/10' }} rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Laporan Transaksi
            </a>
            <a href="{{ route('admin.attendance') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('admin.attendance') ? 'bg-teal-900 dark:bg-white/15 text-white dark:ring-1 dark:ring-white/20' : 'hover:bg-teal-700 dark:hover:bg-white/10' }} rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Rekap Presensi
            </a>
            <a href="{{ route('admin.eod') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('admin.eod*') ? 'bg-teal-900 dark:bg-white/15 text-white dark:ring-1 dark:ring-white/20' : 'hover:bg-teal-700 dark:hover:bg-white/10' }} rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                Rekap Harian (EOD)
            </a>
            <a href="{{ route('admin.users') }}" class="flex items-center px-4 py-3 {{ request()->routeIs('admin.users*') ? 'bg-teal-900 dark:bg-white/15 text-white dark:ring-1 dark:ring-white/20' : 'hover:bg-teal-700 dark:hover:bg-white/10' }} rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Kelola User
            </a>
        </nav>
        <div class="p-4 border-t border-teal-700 dark:border-white/10 shrink-0">
            <button type="button" onclick="document.getElementById('logoutModal').classList.remove('hidden')" class="flex items-center px-4 py-2 hover:bg-teal-700 dark:hover:bg-white/10 rounded transition-colors text-sm w-full text-left cursor-pointer">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Logout
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 min-w-0 flex flex-col overflow-hidden">
        <header class="h-16 bg-white dark:bg-zinc-900 border-b border-gray-200 dark:border-zinc-800 flex items-center px-4 sm:px-6 justify-between gap-3 shrink-0 transition-colors duration-200">
            <div class="flex items-center gap-3 min-w-0">
                <x-sidebar-toggle class="p-2 shrink-0" />
                <h2 class="text-lg sm:text-xl font-semibold text-gray-800 dark:text-zinc-100 truncate">@yield('page_title', 'Dashboard')</h2>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <x-theme-toggle class="p-2" />
                <span class="hidden sm:inline text-sm font-medium text-gray-600 dark:text-zinc-300">Halo, {{ auth()->user()->full_name }}</span>
                <img class="h-8 w-8 rounded-full bg-gray-300 dark:bg-zinc-700" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->full_name) }}&background=0D8ABC&color=fff" alt="Profile">
            </div>
        </header>
        
        <div class="flex-1 overflow-auto p-6">
            @yield('admin_content')
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
            <h3 class="text-lg font-medium text-gray-900 dark:text-zinc-100 mb-2">Konfirmasi Logout</h3>
            <p class="text-sm text-gray-500 dark:text-zinc-400 mb-6">Apakah Anda yakin ingin keluar dari aplikasi?</p>
            <div class="flex justify-center space-x-3">
                <button type="button" onclick="document.getElementById('logoutModal').classList.add('hidden')" class="px-4 py-2 bg-white dark:bg-zinc-800 border border-gray-300 dark:border-zinc-700 rounded-lg text-sm font-medium text-gray-700 dark:text-zinc-200 hover:bg-gray-50 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 cursor-pointer">
                    Batal
                </button>
                <form action="{{ route('logout') }}" method="POST" class="inline-block">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 cursor-pointer">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
