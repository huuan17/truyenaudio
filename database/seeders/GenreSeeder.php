<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Genre;

class GenreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $genres = [
            'Trung Sinh',
            'Tiên Hiệp',
            'Huyền Huyễn',
            'Đô Thị',
            'Khoa Huyễn',
            'Lịch Sử',
            'Quân Sự',
            'Đồng Nhân',
            'Cạnh Kỹ',
            'Linh Dị'
        ];

        foreach ($genres as $genreName) {
            Genre::firstOrCreate(['name' => $genreName]);
        }
    }
}
