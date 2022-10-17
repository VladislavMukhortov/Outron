<?php

namespace App\Models;
use Database\Factories\MediaFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property-read int id
 * @property string model_type
 * @property int model_id
 * @property string|null uuid
 * @property string collection_name
 * @property string name
 * @property string file_name
 * @property string|null mime_type
 * @property string disk
 * @property string|null conversions_disk
 * @property int size
 * @property string manipulations
 * @property string custom_properties
 * @property string generated_conversions
 * @property string responsive_images
 * @property int|null order_column
 * @property Carbon|null created_at
 * @property Carbon|null updated_at
 *
 * @method static MediaFactory factory($count = null, $state = [])
*/
class Media extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $table = 'media';

    // TODO проверить, если добавление картинок будет работать после удаления этого массива. И проверить в Нове
    protected $fillable = [
        'model_type',
        'model_id',
        'uuid',
        'collection_name',
        'name',
        'file_name',
        'mime_type',
        'disk',
        'conversions_disk',
        'size',
        'manipulations',
        'custom_properties',
        'generated_conversions',
        'responsive_images',
        'order_column',
    ];
}
