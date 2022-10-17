<?php

use App\Http\Controllers\Api\v1\BookingController;
use App\Http\Controllers\Api\v1\CityController;
use App\Http\Controllers\Api\v1\ContactController;
use App\Http\Controllers\Api\v1\CountryController;
use App\Http\Controllers\Api\v1\Hotel\HotelController;
use App\Http\Controllers\Api\v1\Hotel\HotelFavoriteController;
use App\Http\Controllers\Api\v1\Hotel\HotelOwnerController;
use App\Http\Controllers\Api\v1\LakeController;
use App\Http\Controllers\Api\v1\LocationController;
use App\Http\Controllers\Api\v1\MealsController;
use App\Http\Controllers\Api\v1\MediaController;
use App\Http\Controllers\Api\v1\RegionController;
use App\Http\Controllers\Api\v1\RoomController;
use App\Http\Controllers\Api\v1\TagController;
use App\Http\Controllers\Api\v1\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::group(['as' => 'api.'], function () {
    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::group(['prefix' => 'profile', 'as' => 'profile.'], function () {
            Route::get('/', [UserProfileController::class, 'index'])->name('index');
            Route::post('/update', [UserProfileController::class, 'update'])->name('update');
            Route::post('/input-phone-code', [UserProfileController::class, 'inputChangePhoneCode'])->name('inputChangePhoneCode');
        });
        Route::group(['prefix' => 'favorites', 'as' => 'favorites.'], function () {
            Route::post('/', [HotelFavoriteController::class, 'index'])->name('index');
            Route::post('/hotels/{hotel}', [HotelFavoriteController::class, 'store'])->name('store');
            Route::delete('/hotels/{hotel}', [HotelFavoriteController::class, 'remove'])->name('remove');
        });
        Route::group(['prefix' => 'owner/hotels', 'as' => 'owner.hotels.'], function () {
            Route::group(['middleware' => 'hotel.owner'], function () {
                Route::post('/upsert/{hotel?}', [HotelOwnerController::class, 'upsert'])->name('upsert');
            });
        });

        Route::post('hotels/booking/{room}', [HotelController::class, 'bookingHotel'])->name('bookingHotel');

        Route::group(['middleware' => 'hotel.owner', 'prefix' => 'hotels', 'as' => 'hotels.'], function () {
            Route::post('/{hotel}/rooms/store', [RoomController::class, 'store'])->name('hotels.rooms.store');
            Route::delete('/{hotel}/rooms/{room}', [RoomController::class, 'remove'])->name('rooms.remove');
            Route::delete('/{hotel}/contacts/{contact}', [ContactController::class, 'remove'])->name('contacts.remove');
            Route::delete('/{hotel}/media/{media}', [MediaController::class, 'remove'])->name('media.remove');
        });
        Route::group(['prefix' => 'bookings', 'as' => 'bookings.'], function () {
            Route::post('/hotels/{hotel}', [BookingController::class, 'prepare'])->name('prepare');

        });
    });

    Route::group(['prefix' => 'hotels', 'as' => 'hotels.'], function () {
        Route::post('/', [HotelController::class, 'index'])->name('index');
        Route::get('/{hotel}', [HotelController::class, 'show'])->name('show');

        Route::post('/{hotelId}/rooms', [RoomController::class, 'getHotelAvailableRooms'])->name('rooms.getHotelAvailableRooms');
        Route::get('/{hotel}/available-rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
    });

    Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
    Route::get('/countries', [CountryController::class, 'index'])->name('countries.index');
    Route::get('/cities', [CityController::class, 'index'])->name('cities.index');
    Route::get('/regions', [RegionController::class, 'index'])->name('regions.index');
    Route::get('/lakes', [LakeController::class, 'index'])->name('lakes.index');
    Route::post('/find-location', [LocationController::class, 'findLocation'])->name('findLocation');
    Route::get('/meals', [MealsController::class, 'index'])->name('meals.index');
});


