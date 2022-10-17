<?php

namespace App\Observers;

use App\Models\Hotel;
use App\Models\User;
use App\Notifications\NewHotelNotification;
use Illuminate\Support\Facades\Notification;

class HotelObserver
{
    public function created(Hotel $hotel): void
    {
        // Send notification to Nova admins when a new hotel created
        $admins = User::query()->where('role_id', User::ROLE_ID_ADMIN)->get();
        Notification::send($admins, new NewHotelNotification($hotel));
    }
}
