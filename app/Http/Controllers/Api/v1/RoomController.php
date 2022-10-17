<?php

namespace App\Http\Controllers\Api\v1;

use App\Exceptions\ApiLogicException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\Room\AvailableRoomRequest;
use App\Http\Resources\HotelRoomResource;
use App\Http\Services\RoomService;
use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomController extends Controller
{
    public function getHotelAvailableRooms(AvailableRoomRequest $availableRoomRequest, int $hotelId): JsonResource
    {
        $roomService = RoomService::create();
        $roomService->setBuilder(Room::query());
        $roomService->setGuestCount($availableRoomRequest->adult_count + $availableRoomRequest->child_count);
        $roomService->setCheckIn($availableRoomRequest->check_in);
        $roomService->setCheckOut($availableRoomRequest->check_out);
        $roomService->setHotelId($hotelId);
        $rooms = $roomService->getAvailableRoomsBuilder()->get();

        $roomGroups = $rooms->groupBy('group_id');
        $rooms = $roomGroups->map(function ($groups) {
            $availableIds = $groups->pluck('id')->toArray();
            $room = $groups->first();
            $room['available_ids'] = $availableIds;

            return $room;
        });

        return HotelRoomResource::collection($rooms->values());
    }

    /**
     * @throws ApiLogicException
     */
    public function remove(Hotel $hotel, Room $room): JsonResponse
    {
        // TODO Тут 2 запроса в базу, надо подумать о транзакции.
        Room::query()
            ->where('hotel_id', $hotel->getKey())
            ->where('id', $room->getKey())
            ->when($room->bookings->count(),
                fn(Builder $q) => throw new ApiLogicException('Этот отель имеет бронирование, его нельзя удалить.')
            )->delete();

        $hotel->status_id = Hotel::STATUS_ID_UNDER_REVIEW;
        $hotel->save();

        return response()->json([
            'message' => 'Комната успешно удалена.',
        ]);
    }
}
