<?php

namespace App\Http\Controllers\Api\v1\Hotel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Hotel\HotelSearchRequest;
use App\Http\Resources\HotelCardResource;
use App\Http\Resources\HotelShowResource;
use App\Http\Services\HotelService;
use App\Models\Hotel;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelController extends Controller
{
    public function index(HotelSearchRequest $hotelSearchRequest): JsonResource
    {
        $hotelService = HotelService::create();
        $hotels = $hotelService->searchHotels($hotelSearchRequest);
        $nextPage = $hotelService->nextPage($hotels);

        return HotelCardResource::collection($hotels)->additional(['next_page' => $nextPage]);
    }

    public function show(Hotel $hotel): JsonResource
    {
        return HotelShowResource::make($hotel);
    }
}
