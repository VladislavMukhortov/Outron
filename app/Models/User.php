<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Image\Exceptions\InvalidManipulation;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property-read int id
 * @property int role_id
 * @property string|null name
 * @property string|null email
 * @property string phone
 * @property Carbon|null email_verified_at
 * @property string|null password
 * @property string|null avatar
 * @property string|null remember_token
 * @property Carbon|null created_at
 * @property Carbon|null updated_at
 *
 * @property-read Collection $hotels
 * @property-read Collection $bookings
 * @property-read Collection $favoriteHotels
 *
 * @method static UserFactory factory($count = null, $state = [])
 */
class User extends Authenticatable implements HasMedia
{
    use InteractsWithMedia, HasApiTokens, HasFactory, Notifiable;

    const ROLE_ID_ADMIN = 1;
    const ROLE_ID_OWNER = 2;
    const ROLE_ID_CLIENT = 3;

    const ROLE_IDS = [
        self::ROLE_ID_ADMIN => 'admin',
        self::ROLE_ID_OWNER => 'owner',
        self::ROLE_ID_CLIENT => 'client',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * @throws InvalidManipulation
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(130)
            ->height(130);
    }

    public function hotels(): HasMany
    {
        return $this->hasMany(Hotel::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class);
    }

    public function favoriteHotels(): BelongsToMany
    {
        return $this->belongsToMany(Hotel::class, 'favorite_hotels');
    }

    public static function findByPhone(?string $phone): ?self
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return self::query()->where('phone', $phone)->first();
    }
}
