@include('layouts.partials.grup-menu-admin', [
    'kunci' => 'edukasi',
    'judul' => 'Edukasi',
    'ikon' => 'buku',
    'butir' => [
        [['admin.edukasi.materi.*', 'admin.edukasi.langkah.*'], 'admin.edukasi.materi.index', 'Daftar materi', 'log'],
        [['admin.edukasi.kategori.*'], 'admin.edukasi.kategori.index', 'Kategori materi', 'kategori'],
    ],
])
