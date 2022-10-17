<?php

namespace App\Http\Resources;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelRoomResource extends JsonResource
{
    private function getResource(): Room
    {
        return $this->resource;
    }

    public function toArray($request): array
    {
        $room = $this->getResource();

        return [
            'name' => $room->name,
            'group_id' => $room->group_id,
            'meals_id' => $room->meals_id,
            'guest_count' => $room->guest_count,
            'preview' => $room->getMedia('preview')->pluck('original_url')->first(),
            'price' => $room->price,
            // TODO custom variable
            'available_ids' => $room['available_ids'],
        ];
    }
}
