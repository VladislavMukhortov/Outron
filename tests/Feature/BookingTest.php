<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use DatabaseMigrations;

    private User $user;
    public Hotel $hotel;
    public Collection $rooms;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->asClient()->create();
        $this->userLogin($this->user->phone);
        $this->hotel = Hotel::factory()->create();
        $this->rooms = Room::factory(2)->for($this->hotel)->create();
    }

    public function testPrepareBooking(): void
    {
        $requestData = [
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
            'adult_count' => 4,
            'child_count' => 2,
            'rooms' => $this->rooms->pluck('id')->toArray(),
        ];
        $this->assertCount(0, Booking::all());

        $response = $this->postJson(route('api.bookings.prepare', ['hotel' => $this->hotel]), $requestData);

        $this->assertCount(1, Booking::all());

        /** @var Booking $booking */
        $booking = Booking::query()->where('hotel_id', $this->hotel->getKey())->first();

        $this->assertCount(2, $booking->rooms);
        $this->assertDatabaseHas('booking_room', [
            'booking_id' => $booking->getKey(),
            'room_id' =>  $booking->rooms->first()->getKey(),
        ]);
        $this->assertDatabaseHas('booking_room', [
            'booking_id' => $booking->getKey(),
            'room_id' =>  $booking->rooms->last()->getKey(),
        ]);

        $response->assertJson([
            'data' => [
                'id' => $booking->getKey(),
                'hotel' => $this->hotel->name,
                'quest_name' => $this->user->name,
                'phone' => $this->user->phone,
                'email' => $this->user->email,
                'adult_count' => $requestData['adult_count'],
                'child_count' => $requestData['child_count'],
                'check_in' => $requestData['check_in'],
                'check_out' => $requestData['check_out'],
                'count_nights' => $booking->count_nights,
                'discount' => $booking->discount,
                'total_price' => $booking->total_price,
            ],
        ]);
    }
}
