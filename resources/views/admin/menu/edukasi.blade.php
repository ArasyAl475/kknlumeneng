@php
    $menuEdukasi = [
        [['admin.edukasi.materi.*', 'admin.edukasi.langkah.*'], 'admin.edukasi.materi.index', 'Daftar materi'],
        [['admin.edukasi.kategori.*'], 'admin.edukasi.kategori.index', 'Kategori materi'],
    ];
@endphp
<p class="mt-4 px-2 text-sm font-bold">Edukasi</p>
<ul class="mt-1 space-y-1">
    @foreach ($menuEdukasi as [$pola, $rute, $label])
        <li>
            <a href="{{ route($rute) }}" @class(['flex min-h-11 items-center rounded px-2 hover:bg-kopi focus-visible:outline-white', 'bg-kopi font-bold' => request()->routeIs($pola)]) @if (request()->routeIs($pola)) aria-current="page" @endif>{{ $label }}</a>
        </li>
    @endforeach
</ul>
