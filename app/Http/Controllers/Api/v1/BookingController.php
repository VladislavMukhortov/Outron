<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Booking\BookingPrepareRequest;
use App\Http\Resources\BookingPrepareResource;
use App\Http\Services\BookingService;
use App\Models\Hotel;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingController extends Controller
{
    public function prepare(BookingPrepareRequest $bookingPrepareRequest, Hotel $hotel): JsonResource
    {
        $bookingService = BookingService::create();
        $booking = $bookingService->createPrepare($bookingPrepareRequest);

     return BookingPrepareResource::make($booking);
    }
}
