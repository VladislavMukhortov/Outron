<?php

namespace App\Http\Resources;

use App\Models\Hotel;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelCardResource extends JsonResource
{
    private function getResource(): Hotel
    {
        return $this->resource;
    }

    public function toArray($request): array
    {
        $hotel = $this->getResource();

        return [
            'id' => $hotel->getKey(),
            'name' => $hotel->name,
            'city' => $hotel->city?->name,
            'preview' => $hotel->getMedia('preview')->pluck('original_url')->first(),
            'min_price' => $hotel->getMinPriceRoom(),
        ];
    }
}
