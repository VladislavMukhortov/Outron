<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterCodeRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Services\User\UserService;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    public function login(RegisterRequest $registerRequest): JsonResponse
    {
        $userService = UserService::create();
        $userService->sendCodeForChangePhone('user_phone_', $registerRequest->phone);

        return response()->json([
            'message' => 'Код выслан вам на телефон.',
            'data' => [
                'phone' => $registerRequest->phone,
            ],
        ]);
    }

    public function inputPrivateCode(RegisterCodeRequest $codeRequest): JsonResponse
    {
        if (!$user = User::findByPhone($codeRequest->phone)) {
            $user = new User();
            $user->phone = $codeRequest->phone;
            $user->save();
        }

        Cache::forget('user_phone_' . $codeRequest->phone);

        Auth::login($user);
        $codeRequest->session()->regenerate();

        return response()->json([
            'message' => 'Вы успешно залогинились.',
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Вы успешно разлогинились.',
        ]);
    }
}
