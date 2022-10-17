<?php

namespace App\Models;

use App\Observers\HotelObserver;
use Carbon\Carbon;
use Database\Factories\HotelFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Image\Exceptions\InvalidManipulation;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property-read int id
 * @property int status_id
 * @property int|null type_id
 * @property string|null name
 * @property string|null description
 * @property int|null country_id
 * @property int|null region_id
 * @property int|null city_id
 * @property string|null address
 * @property string|null coordinates
 * @property int|null distance_city
 * @property string|null detailed_route
 * @property string|null conditions
 * @property int|null season_id
 * @property int|null min_days
 * @property int|null check_in_hour
 * @property int|null check_out_hour
 * @property int user_id
 * @property Carbon|null created_at
 * @property Carbon|null updated_at
 *
 * @property-read User $user
 * @property-read Country|null $country
 * @property-read Region|null $region
 * @property-read City|null $city
 * @property-read Collection $rooms
 * @property-read Collection $roomsGroup
 * @property-read Collection $contacts
 * @property-read Collection $lakes
 * @property-read Collection $bookings
 * @property-read Collection $tags
 * @property-read Collection $media
 *
 * @method static HotelFactory factory($count = null, $state = [])
 *
 * @see HotelObserver
 */
class Hotel extends Model implements HasMedia, Auditable
{
    use HasFactory, InteractsWithMedia, \OwenIt\Auditing\Auditable;

    const TYPE_ID_HOTEL = 1;
    const TYPE_ID_BASE = 2;
    const TYPE_ID_SANATORIUM = 3;

    const TYPE_IDS = [
        self::TYPE_ID_HOTEL => 'отель',
        self::TYPE_ID_BASE => 'база отдыха',
        self::TYPE_ID_SANATORIUM => 'санаторий',
    ];

    const SEASON_ID_FULL = 1;
    const SEASON_ID_WARM = 2;
    const SEASON_ID_WARM_NY = 3;

    const SEASON_IDS = [
        self::SEASON_ID_FULL => 'Круглогодично',
        self::SEASON_ID_WARM => 'В теплое время года',
        self::SEASON_ID_WARM_NY => 'В теплое время года и новогодние праздники',
    ];

    const STATUS_ID_DRAFT = 1;
    const STATUS_ID_UNDER_REVIEW = 2;
    const STATUS_ID_ACTIVE = 3;

    const STATUS_IDS = [
        self::STATUS_ID_DRAFT => 'Черновик',
        self::STATUS_ID_UNDER_REVIEW => 'На рассмотрении',
        self::STATUS_ID_ACTIVE => 'Активен',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function roomsGroup(): Collection
    {
        $roomsGroup = $this->rooms->groupBy('group_id');
        $rooms = $roomsGroup->map(function ($groups) {
            $availableIds = $groups->pluck('id');
            $room = $groups->first();
            $room['quantity'] = count($availableIds);

            return $room;
        });

        return $rooms->values();
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function lakes(): BelongsToMany
    {
        return $this->belongsToMany(Lake::class)->withPivot('distance_shore');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function getMinPriceRoom(): int|null
    {
        return $this->rooms->min('price');
    }
}
