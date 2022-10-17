<?php

namespace App\Http\Adapters;

use App\Models\Contact;
use App\Models\Hotel;
use App\Models\Lake;
use App\Models\Room;

class HotelStoreDataAdapter
{
    public static function transform(Hotel $hotel): array
    {

        $hotel->load('tags', 'contacts', 'lakes');

        $adapter = collect([
            'type_id' => $hotel->type_id,
            'name' => $hotel->name,
            'description' => $hotel->description,
            'country_id' => $hotel->country_id,
            'region_id' => $hotel->region_id,
            'city_id' => $hotel->city_id,
            'address' => $hotel->address,
            'coordinates' => $hotel->coordinates,
            'distance_city' => $hotel->distance_city,
            'detailed_route' => $hotel->detailed_route,
            'conditions' => $hotel->conditions,
            'season_id' => $hotel->season_id,
            'min_days' => $hotel->min_days,
            'check_in_hour' => $hotel->check_in_hour,
            'check_out_hour' => $hotel->check_out_hour,
            'status_id' => $hotel->status_id,
        ]);

        $adapter->put('rooms', $hotel->roomsGroup()->map(function (Room $room) {

            return [
                'id' => $room->getKey(),
                'name' => $room->name,
                'description' => $room->description,
                'guest_count' => $room->guest_count,
                'meals_id' => $room->meals_id,
                'price' => $room->price,
                'price_weekend' => $room->price_weekend,
                'quantity' => $room['quantity'],
            ];
        })->toArray());
        $adapter->put('tags', $hotel->tags->pluck('id')->toArray());
        $adapter->put('lakes', $hotel->lakes->map(function (Lake $lake) {
            return [
                'lake_id' => $lake->getKey(),
                'distance_shore' => $lake->pivot->distance_shore,
            ];
        })->toArray());
        $adapter->put('contacts', $hotel->contacts->map(function (Contact $contact) {
            return [
                'id' => $contact->getKey(),
                'type_id' => $contact->type_id,
                'value' => $contact->value,
            ];
        })->toArray());

        return $adapter->toArray();
    }
}
