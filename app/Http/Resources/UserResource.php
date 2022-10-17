<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    private function getResource(): User
    {
        return $this->resource;
    }

    public function toArray($request): array
    {
        $user = $this->getResource();

        return [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->getMedia('avatars')->first()->original_url ?? null,
            'phone' => $user->phone,
        ];
    }
}
