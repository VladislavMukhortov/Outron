<?php

namespace App\Http\Requests\Api\v1\User;

use App\Http\Requests\Api\OutronApiRequest;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * @property User $user
 * @property string $code
 * @property string $phone
 */
class UserChangePhoneRequest extends OutronApiRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
            //TODO сделать нормальную валидацию телефонов кастомную, по всему приложению
            'phone' => ['required', 'string', 'min:6', 'max:20'],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function rulesPassed(): void
    {
        $code = Cache::get('new_phone_' . $this->phone);

        if (!$code || $code !== $this->code) {
            throw ValidationException::withMessages([
                'code' => 'Код не подходит или срок его действия истек.',
            ]);
        }
    }

    public function attributes(): array
    {
        return [
            'code' => 'Код',
            'phone' => 'Телефон',
        ];
    }
}
