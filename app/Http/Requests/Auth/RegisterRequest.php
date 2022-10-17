<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Api\OutronApiRequest;

/**
 * @property-read string phone
 */
class RegisterRequest extends OutronApiRequest
{
    public function rules(): array
    {
        return [
            //TODO сделать нормальную валидацию телефонов кастомную, по всему приложению
            'phone' => ['required', 'string', 'min:6', 'max:20'],
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => 'Телефон',
        ];
    }
}
