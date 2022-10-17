<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Api\OutronApiRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * @property-read string code
 * @property-read string phone
 */
class RegisterCodeRequest extends OutronApiRequest
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
        $code = Cache::get('user_phone_' . $this->phone);

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
