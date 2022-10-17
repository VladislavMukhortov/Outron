<?php

namespace App\Http\Requests\Api\v1\Booking;

use App\Http\Requests\Api\OutronApiRequest;
use App\Models\Hotel;

/**
 * /**
 * @property-read Hotel hotel
 * @property-read string check_in
 * @property-read string check_out
 * @property-read int adult_count
 * @property-read int child_count
 * @property-read array rooms
 * @property-read int discount
 */

class BookingPrepareRequest extends OutronApiRequest
{
    public function rules(): array
    {
        return [
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in', 'after:today'],
            'adult_count' => ['required', 'numeric', 'min:1', 'max:30'],
            'child_count' => ['filled', 'nullable', 'numeric', 'min:0', 'max:30'],
            'rooms' => ['required', 'array'],
            'rooms.*' => ['required', 'integer', 'exists:rooms,id'],
            'discount' => ['nullable', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'check_in' => 'Дата заезда',
            'check_out' => 'Дата выезда',
            'rooms' => 'Номера',
        ];
    }
}
