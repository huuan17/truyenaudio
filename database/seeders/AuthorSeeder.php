<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Author;
use App\Models\Story;

class AuthorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo một số tác giả mẫu
        $authors = [
            [
                'name' => 'Nguyễn Nhật Ánh',
                'slug' => 'nguyen-nhat-anh',
                'bio' => 'Nguyễn Nhật Ánh là một nhà văn nổi tiếng của Việt Nam, được biết đến với những tác phẩm văn học thiếu nhi và tuổi teen. Ông sinh năm 1955 tại Quảng Nam và đã viết nhiều tác phẩm được yêu thích như "Tôi thấy hoa vàng trên cỏ xanh", "Mắt biếc", "Cho tôi xin một vé đi tuổi thơ".',
                'nationality' => 'Việt Nam',
                'birth_date' => '1955-05-07',
                'achievements' => [
                    'Giải thưởng Hội Nhà văn Việt Nam',
                    'Giải thưởng Sách hay dành cho thiếu nhi',
                    'Tác phẩm được chuyển thể thành phim'
                ],
                'is_active' => true,
                'meta_title' => 'Nguyễn Nhật Ánh - Tác giả nổi tiếng Việt Nam',
                'meta_description' => 'Tìm hiểu về tác giả Nguyễn Nhật Ánh và những tác phẩm nổi tiếng như Tôi thấy hoa vàng trên cỏ xanh, Mắt biếc. Đọc và nghe truyện audio của Nguyễn Nhật Ánh.',
                'meta_keywords' => 'Nguyễn Nhật Ánh, tác giả Việt Nam, văn học thiếu nhi, Mắt biếc, Tôi thấy hoa vàng trên cỏ xanh'
            ],
            [
                'name' => 'Nam Cao',
                'slug' => 'nam-cao',
                'bio' => 'Nam Cao (1915-1951) là một nhà văn hiện thực xuất sắc của văn học Việt Nam hiện đại. Ông được biết đến với những tác phẩm như "Chí Phèo", "Lão Hạc", "Sống mòn". Phong cách viết của Nam Cao mang đậm tính nhân văn, thể hiện sự đồng cảm sâu sắc với số phận con người.',
                'nationality' => 'Việt Nam',
                'birth_date' => '1915-10-29',
                'achievements' => [
                    'Tác phẩm được đưa vào chương trình giáo dục',
                    'Giải thưởng Nhà nước về văn học nghệ thuật',
                    'Tác phẩm được dịch ra nhiều thứ tiếng'
                ],
                'is_active' => true,
                'meta_title' => 'Nam Cao - Nhà văn hiện thực Việt Nam',
                'meta_description' => 'Khám phá tác phẩm của nhà văn Nam Cao với những truyện ngắn nổi tiếng như Chí Phèo, Lão Hạc. Đọc và nghe truyện audio của Nam Cao.',
                'meta_keywords' => 'Nam Cao, nhà văn Việt Nam, Chí Phèo, Lão Hạc, văn học hiện thực'
            ],
            [
                'name' => 'Tô Hoài',
                'slug' => 'to-hoai',
                'bio' => 'Tô Hoài (1920-2014) là nhà văn nổi tiếng với những tác phẩm văn học thiếu nhi. Ông được biết đến nhiều nhất qua tác phẩm "Dế Mèn phiêu lưu ký". Phong cách viết của Tô Hoài giản dị, gần gũi và mang tính giáo dục cao.',
                'nationality' => 'Việt Nam',
                'birth_date' => '1920-09-27',
                'achievements' => [
                    'Giải thưởng Hồ Chí Minh về văn học nghệ thuật',
                    'Nghệ sĩ Nhân dân',
                    'Tác phẩm được dịch ra nhiều thứ tiếng'
                ],
                'is_active' => true,
                'meta_title' => 'Tô Hoài - Tác giả Dế Mèn phiêu lưu ký',
                'meta_description' => 'Tìm hiểu về nhà văn Tô Hoài và tác phẩm nổi tiếng Dế Mèn phiêu lưu ký. Đọc và nghe truyện audio của Tô Hoài.',
                'meta_keywords' => 'Tô Hoài, Dế Mèn phiêu lưu ký, văn học thiếu nhi, truyện audio'
            ],
            [
                'name' => 'Vũ Trọng Phụng',
                'slug' => 'vu-trong-phung',
                'bio' => 'Vũ Trọng Phụng (1912-1939) là nhà văn hiện thực phê phán nổi tiếng của văn học Việt Nam. Mặc dù cuộc đời ngắn ngủi nhưng ông đã để lại những tác phẩm có giá trị như "Số đỏ", "Dumb Luck", thể hiện sự quan sát sắc sảo về xã hội.',
                'nationality' => 'Việt Nam',
                'birth_date' => '1912-10-20',
                'achievements' => [
                    'Tác phẩm được đưa vào chương trình giáo dục',
                    'Tác phẩm được dịch ra tiếng Anh',
                    'Được coi là nhà văn hiện thực phê phán xuất sắc'
                ],
                'is_active' => true,
                'meta_title' => 'Vũ Trọng Phụng - Nhà văn hiện thực phê phán',
                'meta_description' => 'Khám phá tác phẩm của nhà văn Vũ Trọng Phụng với tiểu thuyết Số đỏ nổi tiếng. Đọc và nghe truyện audio của Vũ Trọng Phụng.',
                'meta_keywords' => 'Vũ Trọng Phụng, Số đỏ, văn học hiện thực, nhà văn Việt Nam'
            ]
        ];

        foreach ($authors as $authorData) {
            $author = Author::create($authorData);
            
            // Gán tác giả cho một số truyện có sẵn (nếu có)
            $stories = Story::whereNull('author_id')->limit(2)->get();
            foreach ($stories as $story) {
                $story->update(['author_id' => $author->id]);
            }
        }

        $this->command->info('Created ' . count($authors) . ' authors successfully!');
    }
}
