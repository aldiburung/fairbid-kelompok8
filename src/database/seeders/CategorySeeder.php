<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Elektronik',
            'Fashion',
            'Koleksi dan Antik',
            'Otomotif',
            'Rumah Tangga',
        ] as $namaKategori) {
            Category::updateOrCreate(
                ['nama_kategori' => $namaKategori],
                ['izinkan_perorangan' => true],
            );
        }
    }
}
