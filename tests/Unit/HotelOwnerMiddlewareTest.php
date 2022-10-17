<?php

namespace Tests\Unit;

use App\Exceptions\ApiHotelOwnerException;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Tests\TestCase;

class HotelOwnerMiddlewareTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();

        $hotelOwner = User::factory()->asOwner()->create();
        $this->userLogin($hotelOwner->phone);
        $this->hotel = Hotel::factory()->for($hotelOwner)->create();
    }

    public function testHotelOwnerCanCreateNewHotel(): void
    {
        $response = $this->postJson(route('api.owner.hotels.upsert'), [
            'name' => 'hotel name',
            'status_id' => Hotel::STATUS_ID_DRAFT,
        ]);

        $response->assertOk();
    }

    public function testHotelOwnerCanUpdateOwnHotel(): void
    {
        $response = $this->postJson(route('api.owner.hotels.upsert', ['hotel' => $this->hotel]), [
            'name' => 'hotel name',
            'status_id' => Hotel::STATUS_ID_DRAFT,
        ]);

        $response->assertOk();
    }

    public function testHotelOwnerCanNotUpdateNotOwnHotel(): void
    {
        $this->withoutExceptionHandling();
        $this->expectException(ApiHotelOwnerException::class);
        $this->expectExceptionMessage('User is not the hotel owner.');
        $this->expectExceptionCode(ResponseAlias::HTTP_UNAUTHORIZED);

        $hotel = Hotel::factory()->create();

        $this->postJson(route('api.owner.hotels.upsert', ['hotel' => $hotel]), [
            'name' => 'hotel name',
            'status_id' => Hotel::STATUS_ID_DRAFT,
        ]);
    }
}
