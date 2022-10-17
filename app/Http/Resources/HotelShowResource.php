<?php

namespace App\Http\Resources;

use App\Http\Services\HotelService;
use App\Models\Hotel;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelShowResource extends JsonResource
{
    private function getResource(): Hotel
    {
        return $this->resource;
    }

    public function toArray($request): array
    {
        $hotel = $this->getResource();
        $hotelService = HotelService::create();

        return [
            'name' => $hotel->name,
            'media' => $hotel->getMedia('media')->pluck('original_url'),
            'preview' => $hotel->getMedia('preview')->pluck('original_url')->first(),
            'description' => $hotel->description,
            'address' => $hotel->address,
            'detailed_route' => $hotel->detailed_route,
            'coordinates' => $hotel->coordinates,
            'conditions' => $hotel->conditions,
            'contacts' => $hotelService->getContacts($hotel),
            'check_in_hour' => $hotel->check_in_hour,
            'check_out_hour' => $hotel->check_out_hour,
        ];
    }
}
