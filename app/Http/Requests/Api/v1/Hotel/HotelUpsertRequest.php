<?php

namespace App\Http\Requests\Api\v1\Hotel;

use App\Http\Adapters\HotelStoreDataAdapter;
use App\Http\Requests\Api\OutronApiRequest;
use App\Models\Contact;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\Tag;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * @property-read Hotel|null hotel
 * @property-read int|null type_id
 * @property-read string|null name
 * @property-read string|null description
 * @property-read int|null country_id
 * @property-read int|null region_id
 * @property-read int|null city_id
 * @property-read string|null address
 * @property-read string|null coordinates
 * @property-read int|null distance_city
 * @property-read string|null detailed_route
 * @property-read string|null conditions
 * @property-read int|null season_id
 * @property-read int|null min_days
 * @property-read int|null check_in_hour
 * @property-read int|null check_out_hour
 * @property-read array tags
 * @property-read array images
 * @property-read array contacts
 * @property-read array lakes
 * @property-read array rooms
 * @property-read int status_id
 */
class HotelUpsertRequest extends OutronApiRequest
{
    protected bool $instantValidate = false;

    public function rules(): array
    {
        return [
            // step 1
            'name' => ['filled', 'string', 'min:2', 'max:255'],
            'type_id' => ['filled', Rule::in(array_keys(Hotel::TYPE_IDS))],
            'description' => ['filled', 'string', 'min:5', 'max:2000'],
            // step 2
            'tags' => ['filled', 'array'],
            'tags.*' => ['filled', Rule::in(Tag::query()->get()->pluck('id'))],
            // step 3
            'images' => ['filled', 'array'],
            'images.*' => ['filled', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            // step 4
            'contacts' => ['filled', 'array'],
            'contacts.*.id' => ['filled', 'exists:contacts,id'],
            'contacts.*.type_id' => ['required', Rule::in(array_keys(Contact::TYPE_IDS))],
            'contacts.*.value' => ['required', 'string'],
            // step 5
            'country_id' => ['filled', 'exists:countries,id'],
            'region_id' => ['filled', 'exists:regions,id'],
            'city_id' => ['filled', 'exists:cities,id'],
            'address' => ['filled', 'string'],
            'distance_city' => ['filled', 'integer', 'min:0', 'max:1000000'],
            'coordinates' => ['filled', 'string', 'min:5', 'max:255'],
            // step 6
            'rooms' => ['filled', 'array'],
            'rooms.*.id' => ['filled', 'integer', 'exists:rooms,id'],
            'rooms.*.name' => ['required', 'string', 'min:2', 'max:255'],
            'rooms.*.description' => ['required', 'string', 'min:10', 'max:1000'],
            'rooms.*.guest_count' => ['required', 'integer', 'min:1', 'max:100'],
            'rooms.*.meals_id' => ['required', Rule::in(array_keys(Room::MEALS_IDS))],
            'rooms.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'rooms.*.price' => ['required', 'integer', 'min:1', 'max:1000000'],
            'rooms.*.price_weekend' => ['required', 'integer', 'min:1', 'max:1000000'],
            'rooms.*.images' => ['filled', 'array'],
            'rooms.*.images.*' => ['filled', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            // step 7
            'lakes' => ['filled', 'array'],
            'lakes.*.lake_id' => ['filled', 'exists:lakes,id'],
            'lakes.*.distance_shore' => ['filled', 'integer', 'min:1', 'max:10000'],
            // step 8
            'conditions' => ['filled', 'string', 'min:5', 'max:2000'],
            'detailed_route' => ['filled', 'string', 'min:5', 'max:2000'],
            'season_id' => ['filled', Rule::in(array_keys(Hotel::SEASON_IDS))],
            'min_days' => ['filled', 'integer', 'min:1', 'max:60'],
            'check_in_hour' => ['filled', 'integer', 'min:1', 'max:60'],
            'check_out_hour' => ['filled', 'integer', 'min:1', 'max:60'],
            // includes inside any step
            'status_id' => ['required', 'integer', Rule::in([
                Hotel::STATUS_ID_DRAFT,
                Hotel::STATUS_ID_UNDER_REVIEW,
            ])],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function validationBeforeUpsert(): void
    {
        Validator::make($this->all(), $this->rules(), $this->messages(), $this->attributes())->validate();
    }

    /**
     * @throws ValidationException
     */
    public function validationBeforeModeration(Hotel $hotel): void
    {
        if ($this->getStatusId() === Hotel::STATUS_ID_DRAFT) {
            return;
        }

        $data = HotelStoreDataAdapter::transform($hotel);
        Validator::make($data, $this->rules(), $this->messages(), $this->attributes())->validate();
    }

    public function getStatusId(): int
    {
        return $this->status_id;
    }

    public function attributes(): array
    {
        return [
            'name' => 'Название',
            'type_id' => 'Тип отеля',
            'description' => 'Описание',

            'tags' => 'Теги',
            'tags.*' => 'Тег',

            'images' => 'Фотографии',
            'images.*' => 'Фотография',

            'contacts' => 'Контанты',
            'contacts.*.type_id' => 'Тип',
            'contacts.*.value' => 'Контакт',

            'country_id' => 'Страна',
            'region_id' => 'Регион',
            'city_id' => 'Город',
            'address' => 'Адрес',
            'distance_city' => 'Расстояние до города',
            'coordinates' => 'Координаты',

            'rooms' => 'Номера',
            'rooms.*.name' => 'Название',
            'rooms.*.description' => 'Описание',
            'rooms.*.guest_count' => 'Количество гостей',
            'rooms.*.meals_id' => 'Питание',
            'rooms.*.quantity' => 'Количество',
            'rooms.*.price' => 'Стоимость',
            'rooms.*.price_weekend' => 'Стоимость на выходные',
            'rooms.*.images' => 'Фотографии',
            'rooms.*.images.*' => 'Фотография',

            'lakes' => 'Ближайщие водоёмы',
            'lakes.*.lake_id' => 'Река, море или озеро',
            'lakes.*.distance_shore' => 'Удалённость от берега, м',

            'conditions' => 'Условия',
            'detailed_route' => 'Подробный маршрут',
            'season_id' => 'Сезон',
            'min_days' => 'Минимальное количество дней',
            'check_in_hour' => 'Час заезда',
            'check_out_hour' => 'Час выезда',
        ];
    }
}
