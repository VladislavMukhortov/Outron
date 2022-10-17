<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\Region;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class LocationTest extends TestCase
{
    use DatabaseMigrations;

    public function testFindLocation(): void
    {
        /** @var Country $country */
        $country = Country::factory()->create();
        /** @var Region $region */
        $region = Region::factory()->for($country)->create();
        /** @var City $city */
        $city = City::factory()->for($region)->for($country)->create();
        $testIp = '255.255.255.255';

        $this->postJson(
            route('api.findLocation'),
            ['ip' => $testIp],
        )->assertOk()
            ->assertJson([
                'data' => [
                    'city' => [
                        'id' => $city->getKey(),
                        'name' => $city->name,
                    ],
                    'region' => [
                        'id' => $region->getKey(),
                        'name' => $region->name,
                    ],
                    'country' => [
                        'id' => $country->getKey(),
                        'name' => $country->name,
                    ],
                ]
            ]);
    }

    public function testValidationIp()
    {
        $testIp = 'foo_bar';

        $this->postJson(
            route('api.findLocation'),
            ['ip' => $testIp],
        )->assertUnprocessable();
    }
}
