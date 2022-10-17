<?php

namespace App\Http\Services;

use App\Http\Requests\Api\v1\Hotel\HotelFavoriteIndexRequest;
use App\Http\Requests\Api\v1\Hotel\HotelSearchRequest;
use App\Models\Contact;
use App\Models\Hotel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class HotelService
{
    use ServiceInstance;

    private int $page;
    private int $perPage;

    public function searchHotels(HotelSearchRequest $hotelSearchRequest): Collection
    {
        $this->setPage($hotelSearchRequest->page ?? 1);
        $this->setPerPage($hotelSearchRequest->per_page ?? config('outron.per_page'));

        $roomService = RoomService::create();

        $hotels = Hotel::query()->with(['city', 'rooms'])
            ->when($hotelSearchRequest->location, function (Builder $query, string $location) {
                $query->where('name', 'LIKE', '%' . $location . '%')
                    ->orWhereHas('city', function (Builder $query2) use ($location) {
                        $query2->where('name', 'LIKE', $location . '%');
                    });
            })
            ->whereHas('rooms', function (Builder $query) use ($roomService, $hotelSearchRequest) {
                $roomService->setBuilder($query);
                $roomService->setGuestCount($hotelSearchRequest->guest_count);
                $roomService->setCheckIn($hotelSearchRequest->check_in);
                $roomService->setCheckOut($hotelSearchRequest->check_out);

                $roomsBuilder = $roomService->getAvailableRoomsBuilder();

                if ($hotelSearchRequest->min_price && $hotelSearchRequest->max_price) {
                    $roomsBuilder->whereBetween('price', [$hotelSearchRequest->min_price, $hotelSearchRequest->max_price]);
                }

                if ($hotelSearchRequest->min_price && !$hotelSearchRequest->max_price) {
                    $roomsBuilder->where('price', '>=', $hotelSearchRequest->min_price);
                }

                if (!$hotelSearchRequest->min_price && $hotelSearchRequest->max_price) {
                    $roomsBuilder->where('price', '<=', $hotelSearchRequest->max_price);
                }

                $roomsBuilder->when($hotelSearchRequest->meals_id, function (Builder $query, int $mealsId) {
                    $query->where('meals_id', $mealsId);
                });
            })
            ->when($hotelSearchRequest->tags, function (Builder $query, array $tags) {
                $query->whereHas('tags', function (Builder $query2) use ($tags) {
                    $query2->whereIn('id', $tags);
                });
            });

        if (!$hotelSearchRequest->location) {
            $hotels
                ->when($hotelSearchRequest->city_id, function (Builder $query) use ($hotelSearchRequest) {
                    $query->where('city_id', $hotelSearchRequest->city_id);
                })
                ->when($hotelSearchRequest->region_id, function (Builder $query) use ($hotelSearchRequest) {
                    $query->where('region_id', $hotelSearchRequest->region_id);
                });
        }

        return $hotels->where('status_id', Hotel::STATUS_ID_ACTIVE)
            ->orderBy($hotelSearchRequest->sort_field ?? 'name', $hotelSearchRequest->sort_direction ?? 'asc')
            ->offset(($this->page - 1) * $this->perPage)
            ->limit($this->perPage + 1)
            ->get();
    }

    public function getFavoriteHotels(HotelFavoriteIndexRequest $hotelFavoriteIndexRequest): Collection
    {
        $this->setPage($hotelFavoriteIndexRequest->page ?? 1);
        $this->setPerPage($hotelFavoriteIndexRequest->per_page ?? config('outron.per_page'));

        return auth_user_or_fail()
            ->favoriteHotels()
            ->with('city', 'rooms')
            ->offset(($this->page - 1) * $this->perPage)
            ->limit($this->perPage + 1)
            ->get();
    }

    public function nextPage(Collection $hotels): ?int
    {
        if ($hotels->count() > $this->perPage) {
            $hotels->pop();

            return  $this->page + 1;
        }

        return null;
    }


    public function getContacts(Hotel $hotel): Collection
    {
        return $hotel->contacts->map(function (Contact $contact) {
            return [
                'value' => $contact->value,
                'type' => Contact::TYPE_IDS[$contact->type_id],
            ];
        });
    }

    private function setPage(int $page): void
    {
        $this->page = $page;
    }

    private function setPerPage(int $perPage): void
    {
        $this->perPage = $perPage;
    }
}
