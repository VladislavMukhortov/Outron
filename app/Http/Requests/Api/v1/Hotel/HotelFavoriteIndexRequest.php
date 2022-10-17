<?php

namespace App\Http\Requests\Api\v1\Hotel;

use App\Http\Requests\Api\OutronApiRequest;

/**
 * @property-read int|null page
 * @property-read int|null per_page
 */
class HotelFavoriteIndexRequest extends OutronApiRequest
{
    public function rules(): array
    {
        return [
            'page' => ['filled', 'nullable', 'integer'],
            'per_page' => ['filled', 'nullable', 'integer'],
        ];
    }
}
