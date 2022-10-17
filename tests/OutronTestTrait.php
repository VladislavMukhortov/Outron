<?php

namespace Tests;

use Illuminate\Support\Facades\Auth;

trait OutronTestTrait
{
    protected function userLogin(string $phone): void
    {
        $this->postJson(route('auth.login'), ['phone' => $phone]);
        $this->postJson(route('auth.inputPrivateCode'), [
            'code' => '11111',
            'phone' => $phone,
        ]);
    }

    protected function userLogOut(): void
    {
        Auth::guard('web')->logout();
    }
}
