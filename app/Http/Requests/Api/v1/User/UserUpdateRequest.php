<?php

namespace App\Http\Requests\Api\v1\User;

use App\Http\Requests\Api\OutronApiRequest;
use App\Rules\UserPhone;

/**
 * @property-read string|null name
 * @property-read string|null avatar
 * @property-read string phone
 * @property-read string|null email
 */
class UserUpdateRequest extends OutronApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['filled', 'nullable', 'string', 'min:2', 'max:30'],
            'avatar' => ['filled', 'nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'phone' => ['required', 'string', 'min:6', 'max:20', new UserPhone()],
            'email' => ['filled', 'nullable', 'email:filter', 'unique:users,email'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Имя',
            'avatar' => 'Аватар',
            'phone' => 'Телефон',
            'email' => 'Электронная почта',
        ];
    }
}
