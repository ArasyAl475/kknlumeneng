{{-- Menu admin modul Profil Desa: dua dropdown, profil desa dan berita. --}}
@include('layouts.partials.grup-menu-admin', [
    'kunci' => 'profil',
    'judul' => 'Profil Desa',
    'ikon' => 'desa',
    'butir' => [
        ['admin.profil.identitas.*', 'admin.profil.identitas.edit', 'Identitas & sambutan', 'desa'],
        ['admin.profil.sejarah.*', 'admin.profil.sejarah.edit', 'Sejarah, visi & misi', 'visi'],
        ['admin.profil.wilayah.*', 'admin.profil.wilayah.edit', 'Data wilayah', 'wilayah'],
        ['admin.profil.kontak.*', 'admin.profil.kontak.edit', 'Kontak kantor desa', 'kontak'],
        ['admin.profil.perangkat.*', 'admin.profil.perangkat.index', 'Perangkat desa', 'profil'],
        ['admin.profil.lembaga.*', 'admin.profil.lembaga.index', 'Lembaga desa', 'lembaga'],
    ],
])

@include('layouts.partials.grup-menu-admin', [
    'kunci' => 'berita',
    'judul' => 'Berita & Artikel',
    'ikon' => 'berita',
    'butir' => [
        [['admin.profil.berita.index', 'admin.profil.berita.edit', 'admin.profil.berita.pratinjau'], 'admin.profil.berita.index', 'Semua tulisan', 'log'],
        ['admin.profil.berita.create', 'admin.profil.berita.create', 'Tulis baru', 'tulis'],
    ],
])

@include('layouts.partials.grup-menu-admin', [
    'kunci' => 'galeri',
    'judul' => 'Galeri Foto',
    'ikon' => 'galeri',
    'butir' => [
        [['admin.profil.galeri.index', 'admin.profil.galeri.edit'], 'admin.profil.galeri.index', 'Semua foto', 'galeri'],
        ['admin.profil.galeri.create', 'admin.profil.galeri.create', 'Tambah foto desa', 'unggah'],
    ],
])
