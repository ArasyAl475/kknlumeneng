<ul class="space-y-1">
    <li><a href="{{ route('admin.beranda') }}" class="flex min-h-11 items-center rounded px-2 hover:bg-kopi focus-visible:outline-white @if (request()->routeIs('admin.beranda')) bg-kopi font-bold @endif">Beranda admin</a></li>
</ul>
{{-- Tiap modul menambah menunya sendiri di resources/views/admin/menu/<modul>.blade.php --}}
@foreach (array_keys(config('modul', [])) as $kunciModul)
    @includeIf('admin.menu.'.$kunciModul)
@endforeach
<ul class="mt-3 space-y-1 border-t border-white/30 pt-3">
    <li><a href="{{ route('landing') }}" class="flex min-h-11 items-center rounded px-2 hover:bg-kopi focus-visible:outline-white">Lihat situs publik</a></li>
    <li>
        <form method="POST" action="{{ route('keluar') }}">
            @csrf
            <button type="submit" class="flex min-h-11 w-full items-center rounded px-2 text-left hover:bg-kopi focus-visible:outline-white">Keluar</button>
        </form>
    </li>
</ul>
