<?php

namespace App\Http\Requests\Api\v1\Hotel;

use App\Http\Requests\Api\OutronApiRequest;
use App\Models\Room;
use Illuminate\Validation\Rule;

/**
 * @property-read string|null sort_field
 * @property-read string|null sort_direction
 * @property-read string|null location
 * @property-read int|null city_id
 * @property-read int|null region_id
 * @property-read int|null guest_count
 * @property-read string|null check_in
 * @property-read string|null check_out
 * @property-read int|null min_price
 * @property-read int|null max_price
 * @property-read int|null meals_id
 * @property-read array|null tags
 * @property-read int|null page
 * @property-read int|null per_page
 */
class HotelSearchRequest extends OutronApiRequest
{
    public function rules(): array
    {
        return [
            'sort_field' => ['filled', 'nullable', 'string'],
            'sort_direction' => ['filled', 'nullable', 'string', Rule::in(['asc', 'desc'])],
            'location' => ['filled', 'nullable', 'string'],
            'city_id' => ['filled', 'nullable', 'integer', 'exists:cities,id'],
            'region_id' => ['filled', 'nullable', 'integer', 'exists:regions,id' ],
            'guest_count' => ['filled', 'nullable', 'integer'],
            'check_in' => ['nullable', 'date', 'after_or_equal:today'],
            'check_out' => ['nullable', 'date', 'after:check_in', 'after:today'],
            'min_price' => ['filled', 'nullable', 'numeric'],
            'max_price' => ['filled', 'nullable', 'numeric'],
            'meals_id' => ['filled', 'nullable', Rule::in(array_keys(Room::MEALS_IDS))],
            'tags' => ['filled', 'nullable', 'array'],
            'tags.*' => ['filled', 'integer'],
            'page' => ['filled', 'nullable', 'integer'],
            'per_page' => ['filled', 'nullable', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'sort_field' => 'Поле сортировки',
            'sort_direction' => 'Направление сортировки',
            'location' => 'Местоположение',
            'guest_count' => 'Количество гостей',
            'check_in' => 'Дата заезда',
            'check_out' => 'Дата выезда',
            'min_price' => 'Минимальная стоимость',
            'max_price' => 'Максимальная стоимость',
            'meals_id' => 'Питание',
            'tags' => 'Теги',
            'page' => 'Страница',
        ];
    }
}
