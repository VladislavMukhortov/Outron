<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class RoomTest extends TestCase
{
    use DatabaseMigrations;

    private Hotel $hotel;

    public function setUp(): void
    {
        parent::setUp();

        $owner = User::factory()->create();
        $this->hotel = Hotel::factory()->for($owner)->create();
        $this->userLogin($owner->phone);
    }

    public function dataInvalidRequest(): array
    {
        return [
            'check_in_too_early' => [
                ['check_in' => Carbon::now()->subDay()->toDateString()],
                ['check_in',],
            ],
            'check_out_too_late' => [
                ['check_out' => Carbon::now()->toDateString()],
                ['check_out',],
            ],
            'invalid_dates' => [
                ['check_in' => 568767, 'check_out' => 865442,],
                ['check_in', 'check_out',],
            ],
            'invalid_adult_count' => [
                ['adult_count' => 'foo',],
                ['adult_count',],
            ],
            'adult_count_is_zero' => [
                ['adult_count' => 0,],
                ['adult_count',],
            ],
            'adult_count_more_30' => [
                ['adult_count' => 31,],
                ['adult_count',],
            ],
            'child_count_more_30' => [
                ['child_count' => 31,],
                ['child_count',],
            ],
            'counts_not_numeric' => [
                ['adult_count' => 'foo', 'child_count' => 'bar'],
                ['adult_count', 'child_count',]
            ],
        ];
    }

    /**
     * @dataProvider dataInvalidRequest
     */
    public function testValidateAvailableRooms(array $request): void
    {
        $this->postJson(
            route('api.hotels.rooms.getHotelAvailableRooms', ['hotelId' => $this->hotel->getKey()]),
            $request,
        )->assertUnprocessable();
    }

    public function testTranslateAvailableRooms(): void
    {
        $requestData = [
            'check_in' => 'foo',
            'check_out' => 'bar',
            'adult_count' => 31,
            'child_count' => 31,
        ];

        $this->postJson(
            route('api.hotels.rooms.getHotelAvailableRooms', ['hotelId' => $this->hotel->getKey()]),
            $requestData,
        )->assertJson([
            "message" => "Значение поля Дата заезда не является датой. (и ещё 6 ошибок)",
            "errors" => [
                "check_in" => [
                    "Значение поля Дата заезда не является датой.",
                    "Значение поля Дата заезда должно быть датой после или равной today."
                ],
                "check_out" => [
                    "Значение поля Дата выезда не является датой.",
                    "Значение поля Дата выезда должно быть датой после Дата заезда.",
                    "Значение поля Дата выезда должно быть датой после today."
                ],
                "adult_count" => [
                    "Значение поля Количество взрослых не может быть больше 30."
                ],
                "child_count" => [
                    "Значение поля Количество детей не может быть больше 30."
                ]
            ],
        ]);
    }

    public function testAvailableRooms(): void
    {
        $requestData = [
            'check_in' => Carbon::today()->toDateString(),
            'check_out' => Carbon::tomorrow()->toDateString(),
            'adult_count' => 2,
            'child_count' => 2,
        ];
        $roomsData = collect([
            [
                'name' => 'first',
                'description' => 'some text',
                'guest_count' => 5,
                'meals_id' => 2,
                'price' => 5000,
                'price_weekend' => 6000,
                'quantity' => 3,
            ],
            [
                'name' => 'second',
                'description' => 'some text',
                'guest_count' => 2,
                'meals_id' => 2,
                'price' => 5000,
                'price_weekend' => 6000,
                'quantity' => 1,
            ],
        ]);

        $rooms = collect();

        $roomsData->each(function (array $roomData) use ($rooms) {
            $quantity = $roomData['quantity'];
            unset($roomData['quantity']);

            /** @var Room $room */
            $room = Room::factory()->for($this->hotel)->create($roomData);
            $rooms->add($room);
            if ($quantity > 1) {
                $roomData['group_id'] = $room->getKey();
                for ($i = 1; $i < $quantity; $i++) {
                    Room::factory()->for($this->hotel)->create($roomData);
                }
            }
        });

        $available_ids = $this->hotel->rooms()->where('name', $roomsData[0]['name'])->pluck('id');
        $request = $this->postJson(route('api.hotels.rooms.getHotelAvailableRooms', ['hotelId' => $this->hotel]), $requestData);
        $request->assertOk();
        /** @var Room $firstRoom */
        $firstRoom = $rooms->first();
        $request->assertJson([
            'data' => [[
                "name" => $roomsData[0]['name'],
                "meals_id" => $roomsData[0]['meals_id'],
                "guest_count" => $roomsData[0]['guest_count'],
                "preview" => $firstRoom->getMedia('preview')->pluck('original_url')->first(),
                "price" => $roomsData[0]['price'],
                "available_ids" => $available_ids->toArray(),
            ]],
        ]);
    }

    public function testAvailableRoomsEmpty(): void
    {
        $requestData = [
            'check_in' => Carbon::today()->toDateString(),
            'check_out' => Carbon::tomorrow()->toDateString(),
            'adult_count' => 10,
            'child_count' => 10,
        ];
        $roomsData = [
            [
                'name' => 'first',
                'description' => 'some text',
                'guest_count' => 1,
                'meals_id' => 2,
                'price' => 5000,
                'price_weekend' => 6000,
            ],
            [
                'name' => 'second',
                'description' => 'some text',
                'guest_count' => 1,
                'meals_id' => 2,
                'price' => 5000,
                'price_weekend' => 6000,
            ],
        ];

        foreach ($roomsData as $roomData) {
            Room::factory()->for($this->hotel)->create($roomData);
        }
        $this->postJson(
            route('api.hotels.rooms.getHotelAvailableRooms', ['hotelId' => $this->hotel]),
            $requestData,
        )->assertOk()->assertJson([
            'data' => [],
        ]);
    }

    public function testRemoveRoom(): void
    {
        $room = Room::factory()->for($this->hotel)->create();

        $this->assertDatabaseHas('rooms', [
            'id' => $room->getKey(),
        ]);

        $this->deleteJson(route('api.hotels.rooms.remove', [
            'hotel' => $this->hotel,
            'room' => $room,
        ]))->assertOk();

        $this->assertDatabaseMissing('rooms', [
            'id' => $room->getKey(),
        ]);

        $this->assertDatabaseHas('hotels', [
            'id' => $this->hotel->getKey(),
            'status_id' => Hotel::STATUS_ID_UNDER_REVIEW,
        ]);
    }

    public function testRemoveNotExistsHotel()
    {
        $room = Room::factory()->for($this->hotel)->create();

        $this->deleteJson(route('api.hotels.rooms.remove', [
            'hotel' => 100500,
            'room' => $room,
        ]))->assertNotFound();
    }

    public function testRemoveNotAuthUser()
    {
        $this->userLogOut();
        $room = Room::factory()->for($this->hotel)->create();
        $this->deleteJson(route('api.hotels.rooms.remove', [
            'hotel' => $this->hotel,
            'room' => $room,
        ]))->assertUnauthorized();
    }

    public function testRemoveNotExistsRoom()
    {
        $this->deleteJson(route('api.hotels.rooms.remove', [
            'hotel' => $this->hotel->getKey(),
            'room' => 100500,
        ]))->assertNotFound();
    }

    public function testRemoveNotOwnerUser()
    {
        $notOwner = User::factory()->create();

        $this->userLogin($notOwner->phone);
        $room = Room::factory()->for($this->hotel)->create();

        $this->actingAs($notOwner)->deleteJson(route('api.hotels.rooms.remove', [
            'hotel' => $this->hotel,
            'room' => $room,
        ]))->assertUnauthorized();
    }

    public function testRemoveRoomWithBooking(): void
    {
        $room = Room::factory()
            ->for($this->hotel)
            ->has(
                Booking::factory()
            )->create();

        $this->deleteJson(route('api.hotels.rooms.remove', [
            'hotel' => $this->hotel,
            'room' => $room,
        ]))->assertStatus(500)
            ->assertJson([
                "error" => "Этот отель имеет бронирование, его нельзя удалить."
            ]);

        $this->assertDatabaseHas('rooms', [
            'id' => $room->getKey(),
        ]);
    }
}
