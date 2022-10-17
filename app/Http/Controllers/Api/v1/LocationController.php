<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Location\FindLocationRequest;
use App\Http\Resources\CityResource;
use App\Http\Resources\CountryResource;
use App\Http\Resources\RegionResource;
use App\Models\City;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function findLocation(FindLocationRequest $findLocationRequest): JsonResponse
    {
        //TODO прикрутить сервис определения ip адресов по городам
        //пока что просто вернем первый город, в нем есть регион и страна

        /** @var City $city */
        $city = City::query()->first();

        return response()->json([
            'data' => [
                'city' => CityResource::make($city),
                'region' => RegionResource::make($city->region),
                'country' => CountryResource::make($city->country),
            ],
        ]);
    }
}
