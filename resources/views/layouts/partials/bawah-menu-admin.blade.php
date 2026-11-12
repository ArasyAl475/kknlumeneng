{{-- Bagian bawah sidebar admin: pengguna yang masuk, lihat situs, dan keluar.
     Dipisah dari daftar menu supaya tombol Keluar selalu terlihat tanpa menggulir sidebar. --}}
@if (auth()->user())
    <div class="menu-admin__pengguna">
        <span class="menu-admin__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name ?? 'A', 0, 1)) }}</span>
        <span class="min-w-0 leading-tight">
            <span class="block truncate font-bold">{{ auth()->user()->name }}</span>
            <span class="block text-xs text-white/70">Admin</span>
        </span>
    </div>
@endif
<div class="mt-2 grid grid-cols-2 gap-2">
    <a href="{{ route('landing') }}" class="menu-admin__aksi">
        @include('layouts.partials.ikon-menu', ['nama' => 'situs'])
        Lihat situs
    </a>
    <form method="POST" action="{{ route('keluar') }}">
        @csrf
        <button type="submit" class="menu-admin__aksi w-full">
            @include('layouts.partials.ikon-menu', ['nama' => 'keluar'])
            Keluar
        </button>
    </form>
</div>
