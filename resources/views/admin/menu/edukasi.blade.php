@php
    // Ikon digambar di sini (bukan di layouts.partials.ikon-menu) karena berkas itu milik bersama;
    // kelas menu-admin__ikon membuat ukuran dan warnanya sama dengan ikon modul lain.
    $menuEdukasi = [
        [
            ['admin.edukasi.materi.*', 'admin.edukasi.langkah.*'], 'admin.edukasi.materi.index', 'Daftar materi',
            'M12 6c-2-1.5-5-2-8.5-2v15c3.5 0 6.5.5 8.5 2 2-1.5 5-2 8.5-2V4C17 4 14 4.5 12 6Zm0 0v15', // buku
        ],
        [
            ['admin.edukasi.kategori.*'], 'admin.edukasi.kategori.index', 'Kategori materi',
            'M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z', // folder
        ],
    ];
@endphp
<p class="menu-admin__judul">Edukasi</p>
<ul class="space-y-0.5">
    @foreach ($menuEdukasi as [$pola, $rute, $label, $gambarIkon])
        @php $aktif = request()->routeIs(...$pola); @endphp
        <li>
            <a href="{{ route($rute) }}" @class(['menu-admin__item', 'is-aktif' => $aktif]) @if ($aktif) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="menu-admin__ikon" aria-hidden="true" focusable="false"><path d="{{ $gambarIkon }}" /></svg>
                <span class="menu-admin__label">{{ $label }}</span>
            </a>
        </li>
    @endforeach
</ul>
