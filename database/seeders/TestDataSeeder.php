<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Contact;
use App\Models\Country;
use App\Models\Hotel;
use App\Models\Lake;
use App\Models\Region;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Filesystem\Filesystem;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $regionsCount = 2;
        $citiesCount = 2;
        $lakesCount = 5;

        $filesystem = new Filesystem;
        $folderPaths = $filesystem->directories(public_path('storage/'));
        foreach ($folderPaths as $folderPath) {
            $filesystem->deleteDirectory($folderPath);
        }
//        dd();
        User::factory()->asAdmin()->create([
            'email' => 'admin@admin.com',
        ]);
        Lake::factory($lakesCount)->create();

        $country = Country::factory()->create(['name' => 'Россия']);
        Region::factory($regionsCount)
            ->for($country)
            ->has(City::factory($citiesCount)->sequence(
                ['name' => 'Анапа'],
                ['name' => 'Геленджик'],
            )->for($country))
            ->create();

        $this->createHotel(User::factory()->asOwner()->withPhone('88888888888'));
        $this->createHotel(User::factory()->asOwner(), 50);

        $this->createGroupedRooms();
    }

    private function createHotel(Factory $userFactory, int $count = 1): void
    {
        $roomsCount = 2;
        $bookingCount = 2;

        Hotel::factory($count)
            ->existingLocation()
            ->for($userFactory)
            ->has(Room::factory($roomsCount)
                ->withMeals(Room::MEALS_ID_1)
                ->has(Booking::factory($bookingCount)
                    ->state(function (array $attributes, Room $room) {
                        return ['hotel_id' => $room->hotel_id];
                    })
                )
            )
            ->has(Contact::factory()->type('phone'))
            ->has(Contact::factory()->type('email'))
            ->hasAttached(Lake::query()->inRandomOrder()->first())
            ->hasAttached(Tag::query()->inRandomOrder()->limit(3)->get())
            ->create();
    }

    private function createGroupedRooms(): void
    {
        $hotel = Hotel::factory()->for(User::factory()->asOwner())->create();

        Room::factory()->for($hotel)->create();

        $roomWithTwoChild = Room::factory()->for($hotel)->create();
        Room::factory()
            ->for($hotel)
            ->has(Booking::factory(2)->for($hotel))
            ->create([
                'group_id' => $roomWithTwoChild->getKey(),
            ]);
        Room::factory()->for($hotel)->create([
            'group_id' => $roomWithTwoChild->getKey(),
        ]);
    }
}
