<ul class="space-y-0.5">
    @include('layouts.partials.item-menu-admin', ['pola' => 'admin.beranda', 'rute' => 'admin.beranda', 'label' => 'Beranda admin', 'ikon' => 'beranda'])
</ul>

{{-- Tiap modul menambah menunya sendiri di resources/views/admin/menu/<modul>.blade.php --}}
@foreach (array_keys(config('modul', [])) as $kunciModul)
    @includeIf('admin.menu.'.$kunciModul)
@endforeach

<ul class="mt-5 space-y-0.5 border-t border-white/10 pt-4">
    <li>
        <a href="{{ route('landing') }}" class="menu-admin__item">
            @include('layouts.partials.ikon-menu', ['nama' => 'situs'])
            <span class="menu-admin__label">Lihat situs publik</span>
        </a>
    </li>
    <li>
        <form method="POST" action="{{ route('keluar') }}">
            @csrf
            <button type="submit" class="menu-admin__item w-full text-left">
                @include('layouts.partials.ikon-menu', ['nama' => 'keluar'])
                <span class="menu-admin__label">Keluar</span>
            </button>
        </form>
    </li>
</ul>

@if (auth()->user())
    <div class="menu-admin__pengguna">
        <span class="menu-admin__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name ?? 'A', 0, 1)) }}</span>
        <span class="min-w-0">
            <span class="block truncate font-bold">{{ auth()->user()->name }}</span>
            <span class="block text-xs text-white/60">Admin</span>
        </span>
    </div>
@endif
