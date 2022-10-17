<?php

namespace App\Rules;

use App\Models\User;
use Illuminate\Contracts\Validation\Rule;

class UserPhone implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return !User::query()
            ->where($attribute, $value)
            ->where($attribute, '!=', auth_user_or_fail()->phone)
            ->exists();
    }

    public function message(): string
    {
        return 'Этот телефон уже занят';
    }
}
