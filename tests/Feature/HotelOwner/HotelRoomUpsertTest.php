<?php

namespace Tests\Feature\HotelOwner;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class HotelRoomUpsertTest extends TestCase
{
    use DatabaseMigrations;

    private User $user;
    private Hotel $hotel;
    private Room $roomWithOutGroup;
    private Room $roomWithGroup;
    private Room $roomChild1;
    private Room $roomChild2;
    private array $requestData;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->asClient()->create();
        $this->userLogin($this->user->phone);
    }

    private function prepareDatabase(int $requestStatus): void
    {
        $this->hotel = Hotel::factory()->for($this->user)->create([
            'status_id' => Hotel::STATUS_ID_DRAFT,
        ]);

        $this->roomWithOutGroup = Room::factory()->for($this->hotel)->create([
            'name' => 'roomWithOutChild name before update',
            'description' => 'roomWithOutChild description before update',
            'guest_count' => 1,
            'meals_id' => Room::MEALS_ID_1,
            'price' => 1000,
            'price_weekend' => 1500,
        ]);

        $this->roomWithGroup = Room::factory()->for($this->hotel)->create([
            'name' => 'roomWithTwoChild name before update',
            'description' => 'roomWithTwoChild description before update',
            'guest_count' => 1,
            'meals_id' => Room::MEALS_ID_1,
            'price' => 1000,
            'price_weekend' => 1500,
        ]);
        $this->roomChild1 = Room::factory()->for($this->hotel)->create([
            'group_id' => $this->roomWithGroup->getKey(),
            'name' => 'roomWithTwoChild name before update',
            'description' => 'roomWithTwoChild description before update',
            'guest_count' => 1,
            'meals_id' => Room::MEALS_ID_1,
            'price' => 1000,
            'price_weekend' => 1500,
        ]);
        $this->roomChild2 = Room::factory()->for($this->hotel)->create([
            'group_id' => $this->roomWithGroup->getKey(),
            'name' => 'roomWithTwoChild name before update',
            'description' => 'roomWithTwoChild description before update',
            'guest_count' => 1,
            'meals_id' => Room::MEALS_ID_1,
            'price' => 1000,
            'price_weekend' => 1500,
        ]);

        $this->requestData = [
            'rooms' => [
                [
                    'id' => $this->roomWithOutGroup->getKey(),
                    'name' => 'roomWithOutChild name after update',
                    'description' => 'roomWithOutChild description after update',
                    'guest_count' => 2,
                    'meals_id' => Room::MEALS_ID_2,
                    'price' => 1001,
                    'price_weekend' => 1501,
                    'quantity' => 1,
                ],
                [
                    'id' => $this->roomWithGroup->getKey(),
                    'name' => 'roomWithTwoChild name after update',
                    'description' => 'roomWithTwoChild description after update',
                    'guest_count' => 4,
                    'meals_id' => Room::MEALS_ID_4,
                    'price' => 1004,
                    'price_weekend' => 1504,
                    'quantity' => 4,
                ],
                [
                    'name' => 'new room name',
                    'description' => 'new room description',
                    'guest_count' => 2,
                    'meals_id' => Room::MEALS_ID_2,
                    'price' => 1002,
                    'price_weekend' => 1502,
                    'quantity' => 2,
                ],
            ],

            'status_id' => $requestStatus,
        ];
    }

    private function assertsBeforeUpdate(): void
    {
        // Rooms before update
        $this->assertCount(4, $this->hotel->rooms);

        // STATUS_ID_DRAFT before update
        $this->assertEquals(Hotel::STATUS_ID_DRAFT, $this->hotel->status_id);

        // TODO test images for rooms
    }

    private function assertsAfterUpdate(int $statusAfterUpdate): void
    {
        $this->hotel->refresh();

        // Rooms updated correctly
        $this->assertCount(7, $this->hotel->rooms);
        $this->assertDatabaseMissing((new Room())->getTable(), [
            'hotel_id' => $this->hotel->getKey(),
            'group_id' => null,
            'name' => 'roomWithOutChild name before update',
            'description' => 'roomWithOutChild description before update',
            'guest_count' => 1,
            'meals_id' => Room::MEALS_ID_1,
            'price' => 1000,
            'price_weekend' => 1500,
        ]);
        $this->assertDatabaseMissing((new Room())->getTable(), [
            'hotel_id' => $this->hotel->getKey(),
            'group_id' => $this->roomWithOutGroup->getKey(),
            'name' => 'roomWithTwoChild name before update',
            'description' => 'roomWithTwoChild description before update',
            'guest_count' => 1,
            'meals_id' => Room::MEALS_ID_1,
            'price' => 1000,
            'price_weekend' => 1500,
        ]);
        $this->assertDatabaseMissing((new Room())->getTable(), [
            'hotel_id' => $this->hotel->getKey(),
            'group_id' => $this->roomWithGroup->getKey(),
            'name' => 'roomWithTwoChild name before update',
            'description' => 'roomWithTwoChild description before update',
            'guest_count' => 1,
            'meals_id' => Room::MEALS_ID_1,
            'price' => 1000,
            'price_weekend' => 1500,
        ]);
        $this->assertDatabaseMissing((new Room())->getTable(), [
            'hotel_id' => $this->hotel->getKey(),
            'group_id' => $this->roomWithGroup->getKey(),
            'name' => 'roomWithTwoChild name before update',
            'description' => 'roomWithTwoChild description before update',
            'guest_count' => 1,
            'meals_id' => Room::MEALS_ID_1,
            'price' => 1000,
            'price_weekend' => 1500,
        ]);

        $this->assertDatabaseHas((new Room())->getTable(), [
            'hotel_id' => $this->hotel->getKey(),
            'group_id' => $this->roomWithOutGroup->getKey(),
            'name' => 'roomWithOutChild name after update',
            'description' => 'roomWithOutChild description after update',
            'guest_count' => 2,
            'meals_id' => Room::MEALS_ID_2,
            'price' => 1001,
            'price_weekend' => 1501,
        ]);
        $this->assertDatabaseHas((new Room())->getTable(), [
            'hotel_id' => $this->hotel->getKey(),
            'group_id' => $this->roomWithGroup->getKey(),
            'name' => 'roomWithTwoChild name after update',
            'description' => 'roomWithTwoChild description after update',
            'guest_count' => 4,
            'meals_id' => Room::MEALS_ID_4,
            'price' => 1004,
            'price_weekend' => 1504,
        ]);
        $this->assertDatabaseHas((new Room())->getTable(), [
            'hotel_id' => $this->hotel->getKey(),
            'group_id' => $this->roomWithGroup->getKey(),
            'name' => 'roomWithTwoChild name after update',
            'description' => 'roomWithTwoChild description after update',
            'guest_count' => 4,
            'meals_id' => Room::MEALS_ID_4,
            'price' => 1004,
            'price_weekend' => 1504,
        ]);
        $this->assertDatabaseHas((new Room())->getTable(), [
            'hotel_id' => $this->hotel->getKey(),
            'group_id' => $this->roomWithGroup->getKey(),
            'name' => 'roomWithTwoChild name after update',
            'description' => 'roomWithTwoChild description after update',
            'guest_count' => 4,
            'meals_id' => Room::MEALS_ID_4,
            'price' => 1004,
            'price_weekend' => 1504,
        ]);
        $this->assertDatabaseHas((new Room())->getTable(), [
            'hotel_id' => $this->hotel->getKey(),
            'group_id' => $this->roomWithGroup->getKey(),
            'name' => 'roomWithTwoChild name after update',
            'description' => 'roomWithTwoChild description after update',
            'guest_count' => 4,
            'meals_id' => Room::MEALS_ID_4,
            'price' => 1004,
            'price_weekend' => 1504,
        ]);
        $this->assertDatabaseHas((new Room())->getTable(), [
            'hotel_id' => $this->hotel->getKey(),
            'group_id' => null,
            'name' => 'new room name',
            'description' => 'new room description',
            'guest_count' => 2,
            'meals_id' => Room::MEALS_ID_2,
            'price' => 1002,
            'price_weekend' => 1502,
        ]);

        $newRoomGroup = Room::query()
            ->where('hotel_id', $this->hotel->getKey())
            ->whereNull('group_id')
            ->where('name', 'new room name')
            ->first();
        $this->assertDatabaseHas((new Room())->getTable(), [
            'hotel_id' => $this->hotel->getKey(),
            'group_id' => $newRoomGroup->getKey(),
            'name' => 'new room name',
            'description' => 'new room description',
            'guest_count' => 2,
            'meals_id' => Room::MEALS_ID_2,
            'price' => 1002,
            'price_weekend' => 1502,
        ]);

        // Status after updated
        $this->assertEquals($statusAfterUpdate, $this->hotel->status_id);

        // TODO test images for rooms
    }

    public function testHotelRoomsCanBeCreated(): void
    {
        $this->prepareDatabase(Hotel::STATUS_ID_DRAFT);
        $this->assertCount(4, Room::all());

        $response = $this->postJson(route('api.owner.hotels.upsert'), $this->requestData);

        $this->assertCount(11, Room::all());
        $this->assertCount(7, Room::query()->where('hotel_id', 2)->get());

        $response->assertOk()->assertJson([
            'message' => 'Данные успешно сохранены.',
            'data' => [
                'hotel_id' => 2,
            ],
        ]);
    }

    public function testHotelRoomsCanBeUpdatedAsDraft(): void
    {
        $status = Hotel::STATUS_ID_DRAFT;
        $this->prepareDatabase($status);
        $this->assertsBeforeUpdate();

        $response = $this->postJson(route('api.owner.hotels.upsert', $this->hotel->getKey()), $this->requestData);

        $this->assertsAfterUpdate($status);

        $response->assertOk()->assertJson([
            'message' => 'Данные успешно сохранены.',
            'data' => [
                'hotel_id' => 1,
            ],
        ]);
    }

    public function testHotelRoomsHaveValidationBeforeSendingToReview(): void
    {
        $this->prepareDatabase(Hotel::STATUS_ID_UNDER_REVIEW);

        unset($this->requestData['rooms'][0]['name']);
        unset($this->requestData['rooms'][1]['name']);
        unset($this->requestData['rooms'][2]['name']);

        $response = $this->postJson(route('api.owner.hotels.upsert', $this->hotel->getKey()), $this->requestData);

        $response->assertUnprocessable()->assertJsonFragment([
            'errors' => [
                'rooms' => [
                    'rooms.0.name'=> [
                        'Поле Название обязательно для заполнения.'
                    ],
                    'rooms.1.name'=> [
                        'Поле Название обязательно для заполнения.'
                    ],
                    'rooms.2.name'=> [
                        'Поле Название обязательно для заполнения.'
                    ],
                ],
            ],
        ]);
    }
}
