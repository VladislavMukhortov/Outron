<?php

namespace App\Http\Services;

use App\Exceptions\ApiHotelValidationException;
use App\Exceptions\ApiLogicException;
use App\Http\Requests\Api\v1\Hotel\HotelUpsertRequest;
use App\Models\Contact;
use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class HotelOwnerService
{
    use ServiceInstance;

    /**
     * @throws ApiHotelValidationException
     */
    public function validationBeforeUpsert(HotelUpsertRequest $hotelUpsertRequest): void
    {
        try {
            // TODO validation for hotel - it doesn't have active status

            $hotelUpsertRequest->validationBeforeUpsert();
        } catch (ValidationException $e) {
            throw (new ApiHotelValidationException())->fromLaravel($e);
        }
    }

    /**
     * @throws ApiLogicException
     */
    public function handle(?Hotel $hotel, HotelUpsertRequest $hotelUpsertRequest): Hotel
    {
        DB::beginTransaction();
        try {
            $hotel = $this->upsert($hotel, $hotelUpsertRequest);
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('Cannot upsert hotel: '. $exception->getMessage(), [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            throw new ApiLogicException('Ошибка при сохранении данных, попробуйте снова или обратитесь в поддержку.');
        }

        DB::commit();

        return $hotel;
    }

    /**
     * @throws ApiHotelValidationException
     */
    public function validationBeforeModeration(Hotel $hotel, HotelUpsertRequest $hotelUpsertRequest): void
    {
        try {
            $hotelUpsertRequest->validationBeforeModeration($hotel);
            if ($hotelUpsertRequest->getStatusId() === Hotel::STATUS_ID_UNDER_REVIEW) {
                $this->sendToReview($hotel);
            }
        } catch (ValidationException $e) {
            throw (new ApiHotelValidationException())->fromLaravel($e);
        }
    }

    private function upsert(?Hotel $hotel, HotelUpsertRequest $hotelUpsertRequest): Hotel
    {
        if (!$hotel) {
            $hotel = new Hotel();
        }

        $hotel->status_id = Hotel::STATUS_ID_DRAFT;
        $hotel->type_id = $hotelUpsertRequest->type_id ?? null;
        $hotel->name = $hotelUpsertRequest->name ?? null;
        $hotel->description = $hotelUpsertRequest->description ?? null;
        $hotel->country_id = $hotelUpsertRequest->country_id ?? null;
        $hotel->region_id = $hotelUpsertRequest->region_id ?? null;
        $hotel->city_id = $hotelUpsertRequest->city_id ?? null;
        $hotel->address = $hotelUpsertRequest->address ?? null;
        $hotel->coordinates = $hotelUpsertRequest->coordinates ?? null;
        $hotel->distance_city = $hotelUpsertRequest->distance_city ?? null;
        $hotel->detailed_route = $hotelUpsertRequest->detailed_route ?? null;
        $hotel->conditions = $hotelUpsertRequest->conditions ?? null;
        $hotel->season_id = $hotelUpsertRequest->season_id ?? null;
        $hotel->min_days = $hotelUpsertRequest->min_days ?? null;
        $hotel->check_in_hour = $hotelUpsertRequest->check_in_hour ?? null;
        $hotel->check_out_hour = $hotelUpsertRequest->check_out_hour ?? null;
        $hotel->user_id = auth_user_or_fail()->getKey();
        $hotel->created_at = $hotel->created_at ?? now();
        $hotel->updated_at = now();
        $hotel->save();

        if ($tags = $hotelUpsertRequest->tags) {
            $hotel->tags()->sync($tags);
        }

        if ($contacts = $hotelUpsertRequest->contacts) {
            $this->addContacts($hotel, $contacts);
        }

        if ($media = $hotelUpsertRequest->file('images')) {
            $this->addMedia($hotel, $media);
        }

        if ($lakes = $hotelUpsertRequest->lakes) {
            $hotel->lakes()->sync($lakes);
        }

        if ($rooms = $hotelUpsertRequest->rooms) {
            $this->addRooms($hotel, $rooms);
        }

        return $hotel;
    }

    private function sendToReview(Hotel $hotel): void
    {
        $hotel->status_id = Hotel::STATUS_ID_UNDER_REVIEW;
        $hotel->save();
    }

    private function addContacts(Hotel $hotel, array $contacts): void
    {
        $contacts = collect($contacts)->map(function (array $item) use ($hotel) {
            return [
                'id' => $item['id'] ?? null,
                'hotel_id' => $hotel->getKey(),
                'type_id' => $item['type_id'],
                'value' => $item['value'],
            ];
        })->toArray();

        Contact::query()->upsert($contacts, ['id'], ['value']);
    }

    private function addMedia(Hotel $hotel, array $media): void
    {
        // TODO Сохранить картинки для hotel
    }

    private function addRooms(Hotel $hotel, array $rooms): void
    {
        collect($rooms)->each(function (array $roomData)  use ($hotel) {
            // Upsert parent hotel
            $room = $this->upsertRoom($hotel, $roomData);
            // Remove any child rooms for the parent room
            $hotel->rooms()->where('group_id', $room->getKey())
                ->whereNot('id', $room->getKey())
                ->delete();

            // Create child rooms if the request has the quantity value > 1
            $quantity = $roomData['quantity'];
            if ($quantity > 1) {
                for ($i = 1; $i < $quantity; $i++) {
                    // To create a new child room we should not have room id
                    $roomData['id'] = null;
                    // To create a new child room we should have parent id
                    $roomData['group_id'] = $room->getKey();
                    $this->upsertRoom($hotel, $roomData);
                }
            }
        });

        // TODO Сохранить картинки для rooms
    }

    private function upsertRoom(Hotel $hotel, array $roomData): Room
    {
        $room = new Room();

        if ($id = $roomData['id'] ?? null) {
            // If room id exists in the request, it needs to check if the room is attached to the hotel
            $room = Room::query()->where('hotel_id', $hotel->getKey())->find($id);

            // If for some reasons request contains wrong room id for the hotel it needs to crate a new room model instance
            if (!$room) {
                $room = new Room();
            }
        }

        $room->hotel_id = $hotel->getKey();
        $room->group_id = $roomData['group_id'] ?? $room->getKey();
        $room->name = $roomData['name'];
        $room->description = $roomData['description'];
        $room->guest_count = $roomData['guest_count'];
        $room->meals_id = $roomData['meals_id'];
        $room->price = $roomData['price'];
        $room->price_weekend = $roomData['price_weekend'];
        $room->save();

        return $room;
    }
}
