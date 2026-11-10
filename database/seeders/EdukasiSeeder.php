<?php

namespace Database\Seeders;

use App\Models\Edukasi\Kategori;
use Illuminate\Database\Seeder;

class EdukasiSeeder extends Seeder
{
    public function run(): void
    {
        // Hanya kategori awal; isi materi diisi admin. Dicari lewat slug karena slug tidak
        // berubah saat kategori diganti namanya, jadi seeder ulang tidak membuat duplikat.
        $kategori = [
            'pengolahan-limbah-kopi' => [
                'nama' => 'Pengolahan Limbah Kopi',
                'tips' => 'Kulit kopi jangan dibuang. Jadikan kompos, pakan ternak, atau campuran media baglog jamur, sehingga limbah kebun menjadi bahan baru.',
            ],
            'baglog' => [
                'nama' => 'Baglog',
                'tips' => 'Baglog bekas panen masih berguna. Hancurkan lalu campur ke kompos atau tanah kebun sebagai pupuk, jangan dibakar.',
            ],
            'jamur' => [
                'nama' => 'Jamur',
                'tips' => 'Bonggol dan jamur yang tidak laku bisa dikeringkan atau diolah jadi kerupuk. Sisanya masuk kompos agar tidak ada yang terbuang.',
            ],
        ];

        foreach ($kategori as $slug => $data) {
            $k = Kategori::query()->firstOrCreate(['slug' => $slug], ['nama' => $data['nama']]);

            // Tips hanya diisi bila masih kosong, supaya tulisan yang sudah diubah admin tidak tertimpa.
            if (blank($k->tips_sirkular)) {
                $k->update(['tips_sirkular' => $data['tips']]);
            }
        }
    }
}
