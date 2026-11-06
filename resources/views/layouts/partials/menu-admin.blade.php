<ul class="space-y-0.5">
    <li>
        <a href="{{ route('admin.beranda') }}"
           class="flex min-h-11 items-center gap-3 rounded-lg px-3 py-2 hover:bg-white/10 focus-visible:outline-white @if (request()->routeIs('admin.beranda')) bg-white/15 font-bold @endif">
            <svg class="size-4 shrink-0 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
            Beranda admin
        </a>
    </li>
</ul>

{{-- Tiap modul menambah menunya sendiri di resources/views/admin/menu/<modul>.blade.php --}}
@foreach (array_keys(config('modul', [])) as $kunciModul)
    @includeIf('admin.menu.'.$kunciModul)
@endforeach

<ul class="mt-3 space-y-0.5 border-t border-white/15 pt-3">
    <li>
        <a href="{{ route('landing') }}"
           class="flex min-h-11 items-center gap-3 rounded-lg px-3 py-2 text-white/80 hover:bg-white/10 hover:text-white focus-visible:outline-white">
            <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M3 12L12 3l9 9v9H3z"/><path d="M9 21V12h6v9"/>
            </svg>
            Lihat situs publik
        </a>
    </li>
    <li>
        <form method="POST" action="{{ route('keluar') }}">
            @csrf
            <button type="submit"
                    class="flex min-h-11 w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-white/80 hover:bg-white/10 hover:text-white focus-visible:outline-white">
                <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
                </svg>
                Keluar
            </button>
        </form>
    </li>
</ul>
