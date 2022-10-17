<?php

namespace App\Http\Resources;

use App\Http\Requests\Api\v1\Booking\BookingPrepareRequest;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingPrepareResource extends JsonResource
{
    private function getResource(): Booking
    {
        return $this->resource;
    }

    /**
     * @param BookingPrepareRequest $request
     */
    public function toArray($request): array
    {
        $booking = $this->getResource();

        return [
            'id' => $booking->getKey(),
            'hotel' => $booking->hotel->name,
            'hotel_photo' => $booking->hotel->getMedia('photo')->pluck('original_url'),
            'quest_name' => $booking->quest_name,
            'phone' => $booking->phone,
            'email' => $booking->email,
            'adult_count' => $booking->adult_count,
            'child_count' => $booking->child_count,
            'check_in' => $booking->check_in->toDateString(),
            'check_out' => $booking->check_out->toDateString(),
            'count_nights' => $booking->count_nights,
            'discount' => $booking->discount,
            'total_price' => $booking->total_price,
            'rooms' => HotelRoomResource::collection(Room::query()->find($request->rooms)),
        ];
    }
}
