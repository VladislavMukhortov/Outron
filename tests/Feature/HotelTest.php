<?php

namespace Tests\Feature;

use App\Http\Services\HotelService;
use App\Models\Booking;
use App\Models\City;
use App\Models\Contact;
use App\Models\Country;
use App\Models\Hotel;
use App\Models\Region;
use App\Models\Room;
use App\Models\Tag;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class HotelTest extends TestCase
{
    use DatabaseMigrations;

    public function dataSearchRequest(): array
    {
        return [
            'поиск_по_тегам' => [1, 0, ['tags' => [1]]],
            'поиск_без_критериев' => [2, 0],
            'свободные_даты' => [2, 0,
                ['check_in' =>  now()->addMonth()->toDateString(), 'check_out' => now()->addMonths(2)->toDateString()]],
            'даты_внутри_диапозона_бронирования' => [1, 1,
                ['check_in' => now()->addDays(2)->toDateString(), 'check_out' => now()->addDays(3)->toDateString()]],
            'дата_заезда_в_день_выезда_бронирования' => [1, 1,
                ['check_in' => now()->addDay()->toDateString(), 'check_out' => now()->addWeeks(3)->toDateString()]],
            'дата_выезда_в_день_заезда_бронирования' => [1, 0,
                ['check_in' => now()->toDateString(), 'check_out' => now()->addDay()->toDateString()]],
            'сортировка_по_полю' => [2, 1, ['sort_field' => 'distance_city']],
            'сортировка_с_обр_направлением' => [2, 0, ['sort_field' => 'distance_city','sort_direction' => 'desc']],
            'поиск_по_названию_города' => [1, 0, ['location' => 'Туапсе']],
            'поиск_по_названию_отеля' => [1, 0, ['location' => 'Альбатрос']],
            'поиск_по_количеству_гостей' => [1, 0, ['guest_count' => 5]],
            'поиск_по_min_цене' => [1, 1, ['min_price' => 9000]],
            'поиск_по_max_цене' => [1, 0, ['max_price' => 5000]],
            'поиск_в_диапозоне_цен' => [1, 0, ['min_price' => '5000', 'max_price' => '8000']],
            'поиск_по_питанию' => [1, 0, ['meals_id' => Room::MEALS_ID_5]],
        ];
    }

    public function dataInvalidRequest(): array
    {
        return [
            'invalid_check_in_check_out' => [
                ['check_in' => now()->subDay()->toDateString(), 'check_out' => now()->toDateString()],
                ['check_in', 'check_out']],
            'invalid_check_out' => [
                ['check_in' => now()->toDateString(), 'check_out' => now()->toDateString()],
                ['check_out']],
            'invalid_dates' => [['check_in' => 568767, 'check_out' => 865442],['check_in', 'check_out']],
            'invalid_guest_count' => [['guest_count' => 'foo'],['guest_count']],
            'invalid_min_price' =>  [['min_price' => 'foo'],['min_price']],
            'invalid_max_price' => [['max_price' => 'foo'],['max_price']],
            'invalid_meals' => [['meals_id' => 85],['meals_id']],
            'invalid_tags' => [['tags' => 'море'],['tags']],
            'invalid_page' => [['page' => 'foo'],['page']],
        ];
    }

    /**
     * @dataProvider dataSearchRequest
     */
    public function testIndexHotel(int $expectedCount, int $expectedHotel, array $requestData = []): void
    {
        $dataHotels = $this->setupDataHotels();

        $res =$this->postJson(route('api.hotels.index', $requestData));

        $res ->assertOk()
            ->assertValid(array_keys($requestData))
            ->assertJsonCount($expectedCount, 'data')
            ->assertJsonPath('data.0.name', $dataHotels['hotels'][$expectedHotel]['name']);
    }

    public function testEmptyIndexHotel(): void
    {
        $this->postJson(route('api.hotels.index'))
            ->assertOk()
            ->assertJson([
                'data' => [],
            ]);
    }

    public function testIndexHotelWithoutAvailableRooms(): void
    {
        $hotel = Hotel::factory()->create();
        $guestCount = 2;

        Room::factory()->for($hotel)->create(['guest_count' => $guestCount]);

        $this->postJson(
            route('api.hotels.index'),
            ['guest_count' => $guestCount + 1]
        )->assertOk()->assertJsonCount(0, 'data');
    }

    public function testIndexPaginateHotel(): void
    {
        Hotel::factory(config('outron.per_page') + 1)->has(Room::factory())->create();

        $this->postJson(route('api.hotels.index'))
            ->assertOk()
            ->assertJsonCount(config('outron.per_page'), 'data')
            ->assertJsonPath('next_page', 2);

        $this->postJson(route('api.hotels.index', ['page' => 2]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('next_page', null);

        $this->postJson(route('api.hotels.index', ['page' => 2, 'per_page' => 4]))
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('next_page', 3);
    }

    /**
     * @dataProvider dataInvalidRequest
     */
    public function testIndexValidateHotel(array $requestInvalid, array $expectedKeys): void
    {
        Hotel::factory(10)->has(Room::factory())->create();
        //TODO Интересное поведение, обсудить с ребятами
        $this->postJson(
            route('api.hotels.index'),
            $requestInvalid,
        )->assertUnprocessable()
            ->assertInvalid($expectedKeys);
    }

    public function testShowHotel(): void
    {
        /** @var Hotel $hotel */
        $hotel = Hotel::factory()->create();
        Contact::factory(2)->for($hotel)->create();
        $hotelService = HotelService::create();
        $hotelContacts = $hotelService->getContacts($hotel);
        $response = $this->getJson(route('api.hotels.show', $hotel));

        $response->assertOk()->assertJson([
            'data' => [
                'name' => $hotel->name,
                'media' => $hotel->getMedia('media')->pluck('original_url')->toArray(),
                'preview' => $hotel->getMedia('preview')->pluck('original_url')->first(),
                'description' => $hotel->description,
                'address' => $hotel->address,
                'detailed_route' => $hotel->detailed_route,
                'coordinates' =>  $hotel->coordinates,
                'conditions' => $hotel->conditions,
                'contacts' => [
                    $hotelContacts[0],
                    $hotelContacts[1],
                ],
                'check_in_hour' => $hotel->check_in_hour,
                'check_out_hour' => $hotel->check_out_hour,
            ],
        ]);
    }

    public function testShowHotelNotExists(): void
    {
        $hotel = Hotel::factory()->create();

        $response = $this->getJson(route('api.hotels.show', $hotel->getKey() + 1));

        $response->assertNotFound();
    }

    public function testTranslateMessageHotel(): void
    {
        Hotel::factory(10)->has(Room::factory())->create();

        $response = $this->postJson(route('api.hotels.index', ['check_in' => 568767, 'check_out' => 865442,]));

        $response->assertInvalid(['check_in', 'check_out'])
            ->assertJson([
                'message' => 'Значение поля Дата заезда не является датой. (и ещё 4 ошибок)',
            ]);
    }

    private function setupDataHotels(): array
    {
        $data = ['country' => 'Россия',
            'region' => 'Краснодарский край',
            'city1' => 'Туапсе',
            'city2' => 'Сочи',
            'tag1' => 'море',
            'tag2' => 'охота',
        ];

        $country = Country::factory()->create(['name' => $data['country']]);
        $region = Region::factory()->for($country)->create(['name' => $data['region']]);
        $city1 = City::factory()->for($country)->for($region)->create(['name' => $data['city1']]);
        $city2 = City::factory()->for($country)->for($region)->create(['name' => $data['city2']]);
        $tag1 = Tag::factory()->create(['name' => $data['tag1']]);
        $tag2 = Tag::factory()->create(['name' => $data['tag2']]);

        Hotel::factory()->existingLocation()->create(['status_id' => Hotel::STATUS_ID_DRAFT]);
        Hotel::factory(7)->for($country)->create();

        $data['hotels'] = [
            ['name' => 'Альбатрос',
                'city' => $city1,
                'tag' => $tag1,
                'distance_city' => 9,
                'rooms' => [
                    [
                        'guest_count' => 5,
                        'meals_id' => Room::MEALS_ID_5,
                        'price' => 5000,
                        'is_booked' => true,
                    ],
                    [
                        'guest_count' => 5,
                        'meals_id' => Room::MEALS_ID_5,
                        'price' => 5000,
                        'is_booked' => false,
                    ],
                ],
                'check_in' => now()->addDay()->toDateString(),
                'check_out' => now()->addWeeks(2)->toDateString(),
            ],
            ['name' => 'Ласточка',
                'city' => $city2,
                'tag' => $tag2,
                'distance_city' => 5,
                'rooms' => [
                    [
                        'guest_count' => 1,
                        'meals_id' => Room::MEALS_ID_1,
                        'price' => 9000,
                        'is_booked' => true,
                    ],
                ],
                'check_in' => now()->toDateString(),
                'check_out' => now()->addDay()->toDateString(),
            ],
        ];

        foreach ($data['hotels'] as $item) {
            /** @var Hotel $hotel */
            $hotel = Hotel::factory()
                ->for($country)
                ->for($region)
                ->for($item['city'])
                ->hasAttached($item['tag'])
                ->create(['name' => $item['name'],'distance_city' => $item['distance_city']]);

            foreach ($item['rooms'] as $room) {
                $isBooked = $room['is_booked'];
                unset($room['is_booked']);

                if ($isBooked) {
                    Booking::factory()
                        ->has(Room::factory()->state($room)->for($hotel))
                        ->for($hotel)
                        ->state(function () use ($item) {
                            return [
                                'check_in' => $item['check_in'],
                                'check_out' => $item['check_out'],
                            ];
                        })->create();
                }
            }
        }

        return $data;
    }
}
