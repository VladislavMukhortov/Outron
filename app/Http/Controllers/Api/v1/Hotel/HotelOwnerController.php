<?php

namespace App\Http\Controllers\Api\v1\Hotel;

use App\Exceptions\ApiHotelValidationException;
use App\Exceptions\ApiLogicException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Hotel\HotelUpsertRequest;
use App\Http\Services\HotelOwnerService;
use App\Models\Hotel;
use Illuminate\Http\JsonResponse;

class HotelOwnerController extends Controller
{
    /**
     * @throws ApiLogicException|ApiHotelValidationException
     */
    public function upsert(HotelUpsertRequest $hotelUpsertRequest, ?Hotel $hotel): JsonResponse
    {
        $hotelOwnerService = HotelOwnerService::create();
        $hotelOwnerService->validationBeforeUpsert($hotelUpsertRequest);
        $hotel = $hotelOwnerService->handle($hotel, $hotelUpsertRequest);
        $hotelOwnerService->validationBeforeModeration($hotel, $hotelUpsertRequest);

        return response()->json([
            'message' => "Данные успешно сохранены.",
            'data' => [
                'hotel_id' => $hotel->getKey(),
            ],
        ]);
    }
}
