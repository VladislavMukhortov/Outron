<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\User\UserChangePhoneRequest;
use App\Http\Requests\Api\v1\User\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Http\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

class UserProfileController extends Controller
{
    public function index(): JsonResource
    {
        return UserResource::make(auth_user_or_fail());
    }

    public function update(UserUpdateRequest $userUpdateRequest): JsonResource|JsonResponse
    {
        $user = auth_user_or_fail();
        $user->name = $userUpdateRequest->name;
        $user->email = $userUpdateRequest->email;

        if ($userUpdateRequest->avatar) {
            $user->clearMediaCollection('avatars')
                 ->addMediaFromRequest('avatar')
                 ->toMediaCollection('avatars');
        }

        $user->save();

        if ($user->phone !== $userUpdateRequest->phone) {
            $userService = UserService::create();
            $userService->sendCodeForChangePhone('new_phone_', $userUpdateRequest->phone);

            return response()->json([
                'message' => 'Код подтверждения отправлен вам на телефон.',
                'data' => [
                    'phone' => $userUpdateRequest->phone,
                ],
            ]);
        }

        return UserResource::make(auth_user_or_fail());
    }

    public function inputChangePhoneCode(UserChangePhoneRequest $userChangePhoneRequest): JsonResponse
    {
        $user = auth_user_or_fail();
        $user->phone = $userChangePhoneRequest->phone;
        $user->save();

        Cache::forget('new_phone_' . $userChangePhoneRequest->phone);

        return response()->json([
            'message' => 'Телефон успешно изменен.',
        ]);
    }
}
