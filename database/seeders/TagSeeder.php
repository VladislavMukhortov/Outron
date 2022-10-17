<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        Tag::factory(10)->sequence(
            [
                'id' => 1,
                'name' => 'Активный отдых',
            ],
            [
                'id' => 2,
                'name' => 'Рыбалка',
            ],
            [
                'id' => 3,
                'name' => 'Охота',
            ],
            [
                'id' => 4,
                'name' => 'На берегу',
            ],
            [
                'id' => 5,
                'name' => 'Баня и сауна',
            ],
            [
                'id' => 6,
                'name' => 'Праздники',
            ],
            [
                'id' => 7,
                'name' => 'Корпоративный отдых',
            ],
            [
                'id' => 8,
                'name' => 'Подводная охота',
            ],
            [
                'id' => 9,
                'name' => 'Кемпинг',
            ],
            [
                'id' => 10,
                'name' => 'Сплав/Поход',
            ],
        )->create();
    }
}
