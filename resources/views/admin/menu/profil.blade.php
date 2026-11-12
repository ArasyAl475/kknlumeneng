{{-- Menu admin modul Profil Desa: dua dropdown, profil desa serta berita dan galeri.
     Berita dan galeri digabung karena keduanya sama-sama konten yang ditulis admin; sidebar jadi lebih pendek. --}}
@include('layouts.partials.grup-menu-admin', [
    'kunci' => 'profil',
    'judul' => 'Profil Desa',
    'ikon' => 'desa',
    'butir' => [
        ['admin.profil.identitas.*', 'admin.profil.identitas.edit', 'Identitas & sambutan', 'desa'],
        ['admin.profil.sejarah.*', 'admin.profil.sejarah.edit', 'Sejarah, visi & misi', 'visi'],
        ['admin.profil.wilayah.*', 'admin.profil.wilayah.edit', 'Data wilayah', 'wilayah'],
        ['admin.profil.potensi.*', 'admin.profil.potensi.index', 'Potensi & penghasil', 'galeri'],
        ['admin.profil.kontak.*', 'admin.profil.kontak.edit', 'Kontak kantor desa', 'kontak'],
        ['admin.profil.perangkat.*', 'admin.profil.perangkat.index', 'Perangkat desa', 'profil'],
        ['admin.profil.lembaga.*', 'admin.profil.lembaga.index', 'Lembaga desa', 'lembaga'],
    ],
])

@include('layouts.partials.grup-menu-admin', [
    'kunci' => 'berita',
    'judul' => 'Berita & Galeri',
    'ikon' => 'berita',
    'butir' => [
        [['admin.profil.berita.index', 'admin.profil.berita.edit', 'admin.profil.berita.pratinjau'], 'admin.profil.berita.index', 'Semua berita', 'log'],
        ['admin.profil.berita.create', 'admin.profil.berita.create', 'Tulis berita baru', 'tulis'],
        [['admin.profil.galeri.index', 'admin.profil.galeri.edit'], 'admin.profil.galeri.index', 'Semua foto', 'galeri'],
        ['admin.profil.galeri.create', 'admin.profil.galeri.create', 'Tambah foto desa', 'unggah'],
    ],
])
