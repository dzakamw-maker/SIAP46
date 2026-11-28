@props(['label' => false])

<button
    type="button"
    data-theme-toggle
    onclick="window.toggleTheme && window.toggleTheme()"
    aria-label="Ganti ke Mode Gelap"
    title="Ganti ke Mode Gelap"
    aria-pressed="false"
    {{ $attributes->merge([
        'class' => 'inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/90 text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:border-zinc-700 dark:bg-zinc-800/90 dark:text-zinc-300 dark:hover:text-white dark:hover:bg-zinc-700 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#005e6a]/40 dark:focus:ring-teal-400/40',
    ]) }}
>
    <svg class="w-4 h-4 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
    </svg>
    <svg class="w-4 h-4 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
    </svg>
    @if($label)
        <span class="text-xs font-semibold dark:hidden">Gelap</span>
        <span class="text-xs font-semibold hidden dark:inline">Terang</span>
    @endif
</button>
