<ul class="space-y-0.5">
    @include('layouts.partials.item-menu-admin', ['pola' => 'admin.beranda', 'rute' => 'admin.beranda', 'label' => 'Beranda admin', 'ikon' => 'beranda'])
</ul>

<p class="menu-admin__judul">Kelola situs</p>
{{-- Tiap modul menambah menunya sendiri di resources/views/admin/menu/<modul>.blade.php,
     memakai layouts.partials.grup-menu-admin agar tampil sebagai dropdown. --}}
<div class="space-y-0.5">
    @foreach (array_keys(config('modul', [])) as $kunciModul)
        @includeIf('admin.menu.'.$kunciModul)
    @endforeach
</div>

<p class="menu-admin__judul">Tampilan situs</p>
<ul class="space-y-0.5">
    @include('admin.menu.desain')
</ul>
