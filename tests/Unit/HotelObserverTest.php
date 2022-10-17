<?php

namespace Tests\Unit;

use App\Models\Hotel;
use App\Models\User;
use App\Notifications\NewHotelNotification;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HotelObserverTest extends TestCase
{
    use DatabaseMigrations;

    public function testHotelObserverHotelCreated()
    {
        Notification::fake();

        $users = User::factory(2)->asAdmin()->create();
        $hotel = Hotel::factory()->for($users->first())->create();

        Notification::assertCount(2);
        Notification::assertSentTo($users, NewHotelNotification::class, function (NewHotelNotification $notification) use ($hotel) {
            return $notification->hotel->getKey() === $hotel->getKey();
        });
    }
}
