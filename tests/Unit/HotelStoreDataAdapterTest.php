<?php

namespace Tests\Unit;

use App\Http\Adapters\HotelStoreDataAdapter;
use App\Models\Contact;
use App\Models\Hotel;
use App\Models\Lake;
use App\Models\Room;
use App\Models\Tag;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class HotelStoreDataAdapterTest extends TestCase
{
    use DatabaseMigrations;

    public function testHotelStoreDataAdapterReturnsCorrectArray(): void
    {
        /** @var Hotel $hotel */
        $hotel = Hotel::factory()
            ->has(Tag::factory(2))
            ->has(Contact::factory(2))
            ->hasAttached(Lake::factory(2), ['distance_shore' => 100])
            ->create();

        /** @var Room $roomWithOutGroup */
        $roomWithOutGroup = Room::factory()->for($hotel)->create();

        /** @var Room $roomWithGroup */
        $roomWithGroup = Room::factory()->for($hotel)->create();

        Room::factory()->for($hotel)->create([
            'group_id' => $roomWithGroup->getKey(),
        ]);
        Room::factory()->for($hotel)->create([
            'group_id' => $roomWithGroup->getKey(),
        ]);

        /** @var Lake $lakeFirst */
        $lakeFirst = $hotel->lakes->first();
        /** @var Lake $lakeLast */
        $lakeLast = $hotel->lakes->last();

        /** @var Contact $contactFirst */
        $contactFirst = $hotel->contacts->first();
        /** @var Contact $contactLast */
        $contactLast = $hotel->contacts->last();

        $data = HotelStoreDataAdapter::transform($hotel);

        $this->assertSame($data, [
            'type_id' => $hotel->type_id,
            'name' => $hotel->name,
            'description' => $hotel->description,
            'country_id' => $hotel->country_id,
            'region_id' => $hotel->region_id,
            'city_id' => $hotel->city_id,
            'address' => $hotel->address,
            'coordinates' => $hotel->coordinates,
            'distance_city' => $hotel->distance_city,
            'detailed_route' => $hotel->detailed_route,
            'conditions' => $hotel->conditions,
            'season_id' => $hotel->season_id,
            'min_days' => $hotel->min_days,
            'check_in_hour' => $hotel->check_in_hour,
            'check_out_hour' => $hotel->check_out_hour,
            'status_id' => $hotel->status_id,
            'rooms' => [
                [
                    'id' => $roomWithOutGroup->getKey(),
                    'name' => $roomWithOutGroup->name,
                    'description' => $roomWithOutGroup->description,
                    'guest_count' => $roomWithOutGroup->guest_count,
                    'meals_id' => $roomWithOutGroup->meals_id,
                    'price' => $roomWithOutGroup->price,
                    'price_weekend' => $roomWithOutGroup->price_weekend,
                    'quantity' => 1,
                ],
                [
                    'id' => $roomWithGroup->getKey(),
                    'name' => $roomWithGroup->name,
                    'description' => $roomWithGroup->description,
                    'guest_count' => $roomWithGroup->guest_count,
                    'meals_id' => $roomWithGroup->meals_id,
                    'price' => $roomWithGroup->price,
                    'price_weekend' => $roomWithGroup->price_weekend,
                    'quantity' => 3,
                ],
            ],
            'tags' => [
                $hotel->tags->first()->getKey(),
                $hotel->tags->last()->getKey(),
            ],
            'lakes' => [
                [
                    'lake_id' => $lakeFirst->getKey(),
                    'distance_shore' => 100,
                ],
                [
                    'lake_id' => $lakeLast->getKey(),
                    'distance_shore' => 100,
                ],
            ],
            'contacts' => [
                [
                    'id' => $contactFirst->getKey(),
                    'type_id' => $contactFirst->type_id,
                    'value' => $contactFirst->value,
                ],
                [
                    'id' => $contactLast->getKey(),
                    'type_id' => $contactLast->type_id,
                    'value' => $contactLast->value,
                ],
            ],
        ]);
    }
}
