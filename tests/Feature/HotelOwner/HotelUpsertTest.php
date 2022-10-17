<?php

namespace Tests\Feature\HotelOwner;

use App\Models\City;
use App\Models\Contact;
use App\Models\Hotel;
use App\Models\Lake;
use App\Models\Room;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class HotelUpsertTest extends TestCase
{
    use DatabaseMigrations;

    private User $user;
    private Room $room;
    private array $hotelData;
    private array $requestData;
    private Collection $lakes;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->asClient()->create();
        $this->userLogin($this->user->phone);
    }

    private function prepareDatabase(int $requestStatus): void
    {
        /** @var City $city */
        $city = City::factory()->create();
        $tags = Tag::factory(3)->create();
        $this->lakes = Lake::factory(2)->create();
        /** @var Room $room */
        $this->room = Room::factory()
            ->for(Hotel::factory()->for($this->user)
                ->has(Tag::factory())
                ->has(Contact::factory(3)->state(new Sequence(
                    [
                        'type_id' => Contact::TYPE_ID_EMAIL,
                        'value' => 'contact type email - should be updated',
                    ],
                    [
                        'type_id' => Contact::TYPE_ID_VK,
                        'value' => 'contact type vk - should not be updated',
                    ],
                    [
                        'type_id' => Contact::TYPE_ID_TELEGRAM,
                        'value' => 'contact type telegram - should not be updated',
                    ],
                )))
                ->hasAttached(Lake::factory())
                ->create([
                    'status_id' => Hotel::STATUS_ID_DRAFT,
                ])
            )->create([
                'name' => 'room name before update',
                'description' => 'room description before update',
                'guest_count' => 1,
                'meals_id' => Room::MEALS_ID_1,
                'price' => 1000,
                'price_weekend' => 1500,
            ]);


        $this->hotelData = [
            'name' => 'hotel name',
            'type_id' => Hotel::TYPE_ID_HOTEL,
            'description' => 'some hotel description',
            'country_id' => $city->country_id,
            'region_id' => $city->region_id,
            'city_id' => $city->getKey(),
            'address' => 'some address',
            'distance_city' => 123,
            'coordinates' => '-35.063639,107.343426',
            'conditions' => 'some conditions',
            'detailed_route' => 'some detailed route',
            'season_id' => Hotel::SEASON_ID_FULL,
            'min_days' => 2,
            'check_in_hour' => 14,
            'check_out_hour' => 12,

            'status_id' => $requestStatus,
        ];

        $this->requestData = $this->hotelData + [
                'tags' => $tags->pluck('id')->toArray(),

                // 'images' => [],

                'contacts' => [
                    [
                        'id' => $this->room->hotel->contacts()
                            ->where('type_id', Contact::TYPE_ID_EMAIL)
                            ->first()
                            ->getKey(),
                        'type_id' => Contact::TYPE_ID_EMAIL,
                        'value' => 'contact type email - updated',
                    ],
                    [
                        'type_id' => Contact::TYPE_ID_SITE,
                        'value' => 'contact type site - new',
                    ]
                ],

                'rooms' => [
                    [
                        'id' => $this->room->getKey(),
                        'name' => 'room name after update',
                        'description' => 'room description after update',
                        'guest_count' => 2,
                        'quantity' => 2,
                        'meals_id' => Room::MEALS_ID_2,
                        'price' => 2000,
                        'price_weekend' => 2500,
                        // 'images' => [],
                    ],
                    [
                        'name' => 'room name new',
                        'description' => 'room description new',
                        'guest_count' => 3,
                        'quantity' => 3,
                        'meals_id' => Room::MEALS_ID_3,
                        'price' => 3000,
                        'price_weekend' => 3500,
                        // 'images' => [],
                    ]
                ],

                'lakes' => [
                    [
                        'lake_id' => $this->lakes->first()->getKey(),
                        'distance_shore' => 100,
                    ],
                    [
                        'lake_id' => $this->lakes->last()->getKey(),
                        'distance_shore' => 200,
                    ],
                ],
            ];
    }

    private function assertsBeforeUpdate(): void
    {
        // Hotel before update
        $this->assertCount(1, Hotel::all());
        $this->assertDatabaseMissing((new Hotel())->getTable(), $this->hotelData);

        // TODO test images for hotel

        // Tags before update
        $this->assertCount(1, $this->room->hotel->tags);

        // Contacts before update
        $this->assertCount(3, $this->room->hotel->contacts);

        // Lakes before update
        $this->assertCount(1, $this->room->hotel->lakes);

        // Rooms before update
        $this->assertCount(1, $this->room->hotel->rooms);

        // STATUS_ID_DRAFT before update
        $this->assertEquals(Hotel::STATUS_ID_DRAFT, $this->room->hotel->status_id);

        // TODO test images for rooms
    }

    private function assertsAfterUpdate(int $statusAfterUpdate): void
    {
        $hotel = $this->room->hotel;
        $hotel->refresh();

        // Hotel updated correctly
        $this->assertCount(1, Hotel::all());
        $this->assertDatabaseHas((new Hotel())->getTable(), $this->hotelData);

        // TODO test images for hotel

        // Tags updated correctly
        $this->assertCount(3, $hotel->tags);

        // Contacts updated correctly
        $this->assertCount(4, $hotel->contacts);
        $this->assertDatabaseMissing((new Contact())->getTable(), [
            'hotel_id' => $hotel->getKey(),
            'type_id' => Contact::TYPE_ID_EMAIL,
            'value' => 'contact type email - should be updated',
        ]);
        $this->assertDatabaseHas((new Contact())->getTable(), [
            'hotel_id' => $hotel->getKey(),
            'type_id' => Contact::TYPE_ID_EMAIL,
            'value' => 'contact type email - updated',
        ]);
        $this->assertDatabaseHas((new Contact())->getTable(), [
            'hotel_id' => $hotel->getKey(),
            'type_id' => Contact::TYPE_ID_SITE,
            'value' => 'contact type site - new',
        ]);
        $this->assertDatabaseHas((new Contact())->getTable(), [
            'hotel_id' => $hotel->getKey(),
            'type_id' => Contact::TYPE_ID_TELEGRAM,
            'value' => 'contact type telegram - should not be updated',
        ]);
        $this->assertDatabaseHas((new Contact())->getTable(), [
            'hotel_id' => $hotel->getKey(),
            'type_id' => Contact::TYPE_ID_VK,
            'value' => 'contact type vk - should not be updated',
        ]);

        // Lakes updated correctly
        $this->assertCount(2, $this->room->hotel->lakes);
        $this->assertDatabaseHas('hotel_lake', [
            'hotel_id' => $hotel->getKey(),
            'lake_id' => $this->lakes->first()->getKey(),
            'distance_shore' => 100,
        ]);
        $this->assertDatabaseHas('hotel_lake', [
            'hotel_id' => $hotel->getKey(),
            'lake_id' => $this->lakes->last()->getKey(),
            'distance_shore' => 200,
        ]);

        // Rooms updated correctly
        $this->assertCount(5, $hotel->rooms);
        $this->assertDatabaseMissing((new Room())->getTable(), [
            'hotel_id' => $hotel->getKey(),
            'name' => 'room name before update',
            'description' => 'room description before update',
            'guest_count' => 1,
            'meals_id' => Room::MEALS_ID_1,
            'price' => 1000,
            'price_weekend' => 1500,
        ]);
        $this->assertDatabaseHas((new Room())->getTable(), [
            'hotel_id' => $hotel->getKey(),
            'name' => 'room name after update',
            'description' => 'room description after update',
            'guest_count' => 2,
            'meals_id' => Room::MEALS_ID_2,
            'price' => 2000,
            'price_weekend' => 2500,
        ]);
        $this->assertDatabaseHas((new Room())->getTable(), [
            'hotel_id' => $hotel->getKey(),
            'name' => 'room name new',
            'description' => 'room description new',
            'guest_count' => 3,
            'meals_id' => Room::MEALS_ID_3,
            'price' => 3000,
            'price_weekend' => 3500,
        ]);

        // Status after updated
        $this->assertEquals($statusAfterUpdate, $hotel->status_id);

        // TODO test images for rooms
    }

    public function testOnlyHotelOwnerCanUpdateHotel(): void
    {
        $hotel = Hotel::factory()->create();

        $response = $this->postJson(route('api.owner.hotels.upsert', $hotel->getKey()), []);

        $response->assertUnauthorized()->assertJson([
            'error' => 'User is not the hotel owner.',
        ]);
    }

    public function testHotelCanBeCreated(): void
    {
        $this->prepareDatabase(Hotel::STATUS_ID_DRAFT);
        $this->assertCount(1, Hotel::all());

        $response = $this ->postJson(route('api.owner.hotels.upsert'), $this->requestData);

        $this->assertCount(2, Hotel::all());

        $response->assertOk()->assertJson([
            'message' => 'Данные успешно сохранены.',
            'data' => [
                'hotel_id' => 2,
            ],
        ]);
    }

    public function testHotelCanBeUpdatedAsDraft(): void
    {
        $status = Hotel::STATUS_ID_DRAFT;
        $this->prepareDatabase($status);
        $this->assertsBeforeUpdate();

        $response = $this->postJson(route('api.owner.hotels.upsert', $this->room->hotel_id), $this->requestData);

        $this->assertsAfterUpdate($status);

        $response->assertOk()->assertJson([
            'message' => 'Данные успешно сохранены.',
            'data' => [
                'hotel_id' => 1,
            ],
        ]);
    }

    public function testHotelHasFinalValidationBeforeSendingToReview(): void
    {
        $this->prepareDatabase(Hotel::STATUS_ID_UNDER_REVIEW);

        unset($this->requestData['lakes'][0]['distance_shore']);
        unset($this->requestData['lakes'][1]['distance_shore']);

        $response = $this->postJson(route('api.owner.hotels.upsert', $this->room->hotel_id), $this->requestData);

        $response->assertUnprocessable()->assertJsonFragment([
            'errors' => [
                'lakes' => [
                    'lakes.0.distance_shore'=> [
                        'Поле Удалённость от берега, м обязательно для заполнения.'
                    ],
                    'lakes.1.distance_shore'=> [
                        'Поле Удалённость от берега, м обязательно для заполнения.'
                    ],
                ],
            ],
        ]);
    }

    public function testHotelCanBeUpdatedAndSendToReview(): void
    {
        $status = Hotel::STATUS_ID_UNDER_REVIEW;
        $this->prepareDatabase($status);
        $this->assertsBeforeUpdate();

        $response = $this->postJson(route('api.owner.hotels.upsert', $this->room->hotel_id), $this->requestData);

        $this->assertsAfterUpdate($status);

        $response->assertOk()->assertJson([
            'message' => 'Данные успешно сохранены.',
            'data' => [
                'hotel_id' => 1,
            ],
        ]);
    }
}
