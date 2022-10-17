<?php

namespace App\Http\Services;

use App\Http\Requests\Api\v1\Booking\BookingPrepareRequest;
use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BookingService
{
    use ServiceInstance;

    public function createPrepare(BookingPrepareRequest $bookingPrepareRequest): Booking
    {
        $booking = new Booking();
        $booking->status_id = Booking::STATUS_ID_PREPARE;
        $booking->hotel_id = $bookingPrepareRequest->hotel->getKey();

        if($user = auth_user_or_null()){
            $booking->user_id = $user->getKey();
            $booking->quest_name = $user->name;
            $booking->phone = $user->phone;
            $booking->email = $user->email;
        }

        $booking->adult_count = $bookingPrepareRequest->adult_count;
        $booking->child_count = $bookingPrepareRequest->child_count ?? 0;
        $booking->check_in = $bookingPrepareRequest->check_in;
        $booking->check_out = $bookingPrepareRequest->check_out;
        $booking->discount = $bookingPrepareRequest->discount ?? 0;

        $countNights = $this->countNights($bookingPrepareRequest->check_in, $bookingPrepareRequest->check_out);
        $booking->count_nights = $countNights;

        $rooms = $bookingPrepareRequest->hotel->rooms->find($bookingPrepareRequest->rooms);
        $booking->total_price = $this->totalPrice($rooms, $countNights, $booking->discount);

        $booking->save();
        $booking->rooms()->saveMany($rooms);

        return $booking;
    }

    private function countNights(string $checkIn, string $checkOut): int
    {
        $checkInDate = Carbon::parse($checkIn);
        $checkOutDate = Carbon::parse($checkOut);

        return $checkOutDate->diffInDays($checkInDate);
    }

    private function totalPrice(Collection $rooms, int $countNights, int $discount): int
    {
        $price = 0;
        $rooms->each(function (Room $room) use (&$price) {
            $price = $price + $room->price;
        });

        return ($price * $countNights) - $discount;
    }
}
