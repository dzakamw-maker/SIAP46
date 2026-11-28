<button
    type="button"
    data-sidebar-toggle
    onclick="window.toggleSidebar && window.toggleSidebar()"
    aria-label="Tutup Sidebar"
    title="Tutup Sidebar"
    aria-expanded="true"
    aria-controls="appSidebar"
    {{ $attributes->merge([
        'class' => 'inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white/90 text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:border-zinc-700 dark:bg-zinc-800/90 dark:text-zinc-300 dark:hover:text-white dark:hover:bg-zinc-700 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#005e6a]/40 dark:focus:ring-teal-400/40',
    ]) }}
>
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <rect x="3" y="4" width="18" height="16" rx="2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 4v16" />
    </svg>
</button>
