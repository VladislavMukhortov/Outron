<?php

namespace App\Http\Services\User;

use App\Http\Services\ServiceInstance;
use Illuminate\Support\Facades\Cache;

class UserService
{
    use ServiceInstance;

    public function sendCodeForChangePhone(string $keyPrefix, string $phone): void
    {
        // TODO
        // $code = rand(1000000, 99999999);
        // Код для тестов
        $code = 11111;
        Cache::put($keyPrefix . $phone, $code, 120);

        //TODO Тут мы отправляем код на телефон
    }
}
